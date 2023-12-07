<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\ReservationResource;
use App\Models\Reservation;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class HostReservationController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Reservation::class);

        $reservations = Reservation::query()
            ->whereRelation('office', 'user_id', '=', auth()->id())
            ->when(
                value: request()->filled('officeId'),
                callback: fn(Builder $query) => $query->where('office_id', request()->query('officeId')),
            )
            ->when(
                value: request()->filled('visitorId'),
                callback: fn(Builder $query) => $query->where('user_id', request()->query('visitorId')),
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
}
