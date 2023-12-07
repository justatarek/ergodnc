<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\OfficeImage\StoreOfficeImageRequest;
use App\Http\Resources\Api\ImageResource;
use App\Models\Image;
use App\Models\Office;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OfficeImageController extends Controller
{
    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreOfficeImageRequest $request, Office $office): JsonResource
    {
        $path = $request->file('image')->storePublicly();

        $image = $office->images()->create([
            'path' => $path
        ]);

        return ImageResource::make($image);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Office $office, Image $image)
    {
        $this->authorize('update', $office);

        throw_if(
            condition: $office->images()->count() === 1,
            exception: ValidationException::withMessages(['image' => 'Cannot delete the only image.']),
        );

        throw_if(
            condition: $office->featured_image_id === $image->id,
            exception: ValidationException::withMessages(['image' => 'Cannot delete the featured image.']),
        );

        Storage::delete($image->path);

        $image->delete();

        return response()->api()->noContent();
    }
}
