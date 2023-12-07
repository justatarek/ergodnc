<?php

namespace App\Http\Controllers\Api;

use App\DTO\OfficeDTO;
use App\DTO\OfficeFilterDTO;
use App\Enums\ReservationStatus;
use App\Filters\Office\OfficeCoordinatesFilter;
use App\Filters\Office\OfficeHostFilter;
use App\Filters\Office\OfficeTagsFilter;
use App\Filters\Office\OfficeVisitorFilter;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\Office\StoreOfficeRequest;
use App\Http\Requests\Api\Office\UpdateOfficeRequest;
use App\Http\Resources\Api\OfficeResource;
use App\Models\Image;
use App\Models\Office;
use App\Services\OfficeService;
use Exception;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfficeController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, OfficeService $officeService): AnonymousResourceCollection
    {
        $filters = [
            OfficeHostFilter::class,
            OfficeVisitorFilter::class,
            OfficeCoordinatesFilter::class,
            OfficeTagsFilter::class,
        ];

        $offices = $officeService
            ->filter(OfficeFilterDTO::fromRequest($request), $filters)
            ->with(['user', 'tags', 'images', 'featuredImage'])
            ->withCount(['reservations' => fn(Builder $query) => $query->where('status', ReservationStatus::Active)])
            ->paginate(20);

        return OfficeResource::collection($offices);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficeRequest $request, OfficeService $officeService): JsonResource|JsonResponse
    {
        try {
            $office = $officeService->store(OfficeDTO::fromStoreRequest($request));

            $office->load(['user', 'tags', 'images', 'featuredImage']);

            return OfficeResource::make($office);
        } catch (Exception $exception) {
            logger()->error($exception);

            return response()->api()->somethingWentWrong();
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Office $office): JsonResource
    {
        $office
            ->loadCount(['reservations' => fn(Builder $query) => $query->where('status', ReservationStatus::Active)])
            ->load(['user', 'tags', 'images', 'featuredImage']);

        return OfficeResource::make($office);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateOfficeRequest $request, OfficeService $officeService, Office $office): JsonResource|JsonResponse
    {
        try {
            $officeService->update($office, OfficeDTO::fromUpdateRequest($request, $office));

            $office->load(['user', 'tags', 'images', 'featuredImage']);

            return OfficeResource::make($office);
        } catch (Exception $exception) {
            logger()->error($exception);

            return response()->api()->somethingWentWrong();
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Office $office): JsonResponse
    {
        $this->authorize('delete', $office);

        throw_if(
            condition: $office->reservations()->active()->exists(),
            exception: ValidationException::withMessages(['office' => 'Cannot delete this office!']),
        );

        $office->images()->each(function (Image $image) {
            Storage::delete($image->path);

            $image->delete();
        });

        $office->delete();

        return response()->api()->noContent();
    }
}
