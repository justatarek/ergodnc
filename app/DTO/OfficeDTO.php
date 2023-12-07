<?php

namespace App\DTO;

use App\Http\Requests\Api\Office\StoreOfficeRequest;
use App\Http\Requests\Api\Office\UpdateOfficeRequest;
use App\Models\Office;

class OfficeDTO
{
    /**
     * @param array<int>|null $tagsIds
     */
    public function __construct(
        public readonly int     $userId,
        public readonly ?int    $featuredImageId,
        public readonly string  $title,
        public readonly string  $description,
        public readonly float   $lat,
        public readonly float   $lng,
        public readonly string  $addressLine1,
        public readonly ?string $addressLine2,
        public readonly bool    $isHidden,
        public readonly int     $pricePerDay,
        public readonly int     $monthlyDiscount,
        public readonly ?array  $tagsIds,
    )
    {
        //
    }

    public static function fromStoreRequest(StoreOfficeRequest $request): static
    {
        return new static(
            userId: $request->user()->id,
            featuredImageId: null,
            title: $request->validated('title'),
            description: $request->validated('description'),
            lat: $request->validated('lat'),
            lng: $request->validated('lng'),
            addressLine1: $request->validated('address_line1'),
            addressLine2: $request->validated('address_line2'),
            isHidden: $request->boolean('is_hidden'),
            pricePerDay: $request->validated('price_per_day'),
            monthlyDiscount: $request->validated('monthly_discount', 0),
            tagsIds: $request->validated('tags'),
        );
    }

    public static function fromUpdateRequest(UpdateOfficeRequest $request, Office $office): static
    {
        return new static(
            userId: $office->user_id,
            featuredImageId: $request->has('featured_image_id') ? $request->validated('featured_image_id') : $office->featured_image_id,
            title: $request->has('title') ? $request->validated('title') : $office->title,
            description: $request->has('description') ? $request->validated('description') : $office->description,
            lat: $request->has('lat') ? $request->validated('lat') : $office->lat,
            lng: $request->has('lng') ? $request->validated('lng') : $office->lng,
            addressLine1: $request->has('address_line1') ? $request->validated('address_line1') : $office->address_line1,
            addressLine2: $request->has('address_line2') ? $request->validated('address_line2') : $office->address_line2,
            isHidden: $request->has('is_hidden') ? $request->boolean('is_hidden') : $office->is_hidden,
            pricePerDay: $request->has('price_per_day') ? $request->validated('price_per_day') : $office->price_per_day,
            monthlyDiscount: $request->has('monthly_discount') ? $request->validated('monthly_discount', 0) : $office->monthly_discount,
            tagsIds: $request->has('tags') ? $request->validated('tags') : $office->tags->pluck('id')->toArray(),
        );
    }
}
