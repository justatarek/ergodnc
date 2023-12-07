<?php

namespace Controllers\Api;

use App\Models\Image;
use App\Models\Office;
use App\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficeImageControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     */
    public function itUploadsAnImageAndStoresItUnderTheOffice(): void
    {
        Storage::fake();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $uploadedImage = UploadedFile::fake()->image('image.jpg');

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->postJson(route('api.offices.images.store', $office), [
            'image' => $uploadedImage,
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.url', Storage::url($uploadedImage->hashName()));

        Storage::assertExists($uploadedImage->hashName());
    }

    /**
     * @test
     */
    public function itDeletesAnImage(): void
    {
        Storage::fake();

        $host = User::factory()->create();
        $office = Office::factory()->has(Image::factory())->for($host)->create();

        $uploadedImage = UploadedFile::fake()->image('image.jpg');
        $image = Image::factory()->for($office, 'imageable')->create([
            'path' => $uploadedImage->hashName(),
        ]);

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->deleteJson(route('api.offices.images.destroy', [
            'office' => $office,
            'image'  => $image,
        ]));

        $response
            ->assertNoContent();

        $this
            ->assertModelMissing($image);

        Storage::assertMissing($uploadedImage->hashName());
    }

    /**
     * @test
     */
    public function itDoesntDeleteImageThatBelongsToAnotherResource(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $anotherOffice = Office::factory()->for($host)->create();
        $image = Image::factory()->for($anotherOffice, 'imageable')->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->deleteJson(route('api.offices.images.destroy', [
            'office' => $office,
            'image'  => $image,
        ]));

        $response
            ->assertNotFound();
    }

    /**
     * @test
     */
    public function itDoesntDeleteTheOnlyImage(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $image = Image::factory()->for($office, 'imageable')->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->deleteJson(route('api.offices.images.destroy', [
            'office' => $office,
            'image'  => $image,
        ]));

        $response
            ->assertUnprocessable()
            ->assertInvalid('image')
            ->assertJsonValidationErrors(['image' => 'Cannot delete the only image.']);
    }

    /**
     * @test
     */
    public function itDoesntDeleteTheFeaturedImage(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->has(Image::factory())->for($host)->create();
        $image = Image::factory()->for($office, 'imageable')->create();

        $office->update([
            'featured_image_id' => $image->id,
        ]);

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->deleteJson(route('api.offices.images.destroy', [
            'office' => $office,
            'image'  => $image,
        ]));

        $response
            ->assertUnprocessable()
            ->assertInvalid('image')
            ->assertJsonValidationErrors(['image' => 'Cannot delete the featured image.']);
    }
}
