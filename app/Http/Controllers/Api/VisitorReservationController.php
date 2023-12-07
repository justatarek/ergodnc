<?php

namespace App\Http\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Reservation\Visitor\StoreVisitorReservationRequest;
use App\Http\Resources\Api\ReservationResource;
use App\Models\Reservation;
use App\Notifications\NewHostReservation;
use App\Notifications\NewVisitorReservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class VisitorReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reservation::class);

        $reservations = Reservation::query()
            ->where('user_id', auth()->id())
            ->when(
                value: request()->filled('officeId'),
                callback: fn(Builder $query) => $query->where('office_id', request()->query('officeId')),
            )
            ->when(
                value: request()->filled('status'),
                callback: fn(Builder $query) => $query->where('status', request()->query('status')),
            )
            ->when(
                value: request()->filled('fromDate') && request()->filled('toDate'),
                callback: fn(Builder $query) => $query->betweenDates(request()->query('fromDate'), request()->query('toDate')),
            )
            ->with('office.featuredImage')
            ->paginate(20);

        return ReservationResource::collection($reservations);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreVisitorReservationRequest $request): JsonResource
    {
        $office = $request->getOffice();

        $reservation = Cache::lock('reservations_office_' . $office->id, 10)->block(3, function () use ($request, $office) {
            if ($office->reservations()->activeBetween($request->validated('start_date'), $request->validated('end_date'))->exists()) {
                throw ValidationException::withMessages([
                    'office_id' => 'You cannot make a reservation during this time',
                ]);
            }

            $numberOfDays = Carbon::parse($request->validated('end_date'))->endOfDay()->diffInDays(Carbon::parse($request->validated('start_date'))->startOfDay()) + 1;

            $price = $numberOfDays * $office->price_per_day;
            if ($numberOfDays >= 28 && $office->monthly_discount) {
                $price = $price - ($price * $office->monthly_discount / 100);
            }

            return Reservation::create([
                'user_id'       => auth()->id(),
                'office_id'     => $office->id,
                'start_date'    => $request->validated('start_date'),
                'end_date'      => $request->validated('end_date'),
                'status'        => ReservationStatus::Active,
                'price'         => $price,
                'wifi_password' => Str::password(),
            ]);
        });

        Notification::send(auth()->user(), new NewVisitorReservation($reservation));
        Notification::send($office->user, new NewHostReservation($reservation));

        $reservation->load(['office']);

        return ReservationResource::make($reservation);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation): JsonResource
    {
        $this->authorize('delete', Reservation::class);

        if ($reservation->status == ReservationStatus::Canceled || $reservation->start_date < now()->toDateString()) {
            throw ValidationException::withMessages([
                'reservation' => 'You cannot cancel this reservation',
            ]);
        }

        $reservation->update([
            'status' => ReservationStatus::Canceled,
        ]);

        $reservation->load(['office']);

        return ReservationResource::make($reservation);
    }
}
