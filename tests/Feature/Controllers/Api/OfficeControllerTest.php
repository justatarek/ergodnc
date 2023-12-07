<?php

namespace Controllers\Api;

use App\Enums\OfficeApprovalStatus;
use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\Tag;
use App\Models\User;
use App\Notifications\OfficePendingApproval;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class OfficeControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     */
    public function itListsAllOfficesInPaginatedWay(): void
    {
        Office::factory(3)->create();

        $response = $this->getJson(route('api.offices.index'));

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data')
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonStructure(['data' => ['*' => ['id', 'title']]]);
    }

    /**
     * @test
     */
    public function itOnlyListsOfficesThatAreNotHiddenAndApproved(): void
    {
        Office::factory(3)->create();

        Office::factory()->hidden()->create();
        Office::factory()->pending()->create();

        $response = $this->getJson(route('api.offices.index'));

        $response
            ->assertOk()
            ->assertJsonCount(3, 'data');
    }

    /**
     * @test
     */
    public function itListsOfficesIncludingHiddenAndUnApprovedIfFilteringForTheCurrentLoggedInUser(): void
    {
        $host = User::factory()->create();

        Office::factory(3)->for($host)->create();

        Office::factory()->hidden()->for($host)->create();
        Office::factory()->pending()->for($host)->create();

        $this->actingAs($host);

        $response = $this->getJson(route('api.offices.index', [
            'hostId' => $host->id
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(5, 'data');
    }

    /**
     * @test
     */
    public function itFiltersByHostId(): void
    {
        Office::factory(3)->create();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        $response = $this->getJson(route('api.offices.index', [
            'hostId' => $host->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itFiltersByVisitorId(): void
    {
        Office::factory(3)->create();
        Reservation::factory()->for(Office::factory())->create();

        $visitor = User::factory()->create();
        $office = Office::factory()->has(Reservation::factory()->for($visitor))->create();

        $response = $this->getJson(route('api.offices.index', [
            'visitorId' => $visitor->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itFiltersByTags(): void
    {
        $tags = Tag::factory(2)->create();
        $office = Office::factory()->hasAttached($tags)->create();
        Office::factory()->hasAttached($tags->first())->create();
        Office::factory()->create();

        $response = $this->getJson(route('api.offices.index', [
            'tags' => $tags->pluck('id')->toArray(),
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $office->id);
    }

    /**
     * @test
     */
    public function itIncludesUserTagsAndImages(): void
    {
        $host = User::factory()->create();
        Office::factory()->for($host)->has(Tag::factory())->has(Image::factory())->create();

        $response = $this->getJson(route('api.offices.index'));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data.0.tags')
            ->assertJsonCount(1, 'data.0.images')
            ->assertJsonPath('data.0.user.id', $host->id);
    }

    /**
     * @test
     */
    public function itReturnsTheNumberOfActiveReservations(): void
    {
        $office = Office::factory()->create();

        Reservation::factory()->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->getJson(route('api.offices.index'));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.reservations_count', 1);
    }

    /**
     * TODO:fix sqlite pow function
     */
    public function itOrdersByDistanceWhenCoordinatesAreProvided(): void
    {
        Office::factory()->create([
            'lat'   => '39.74051727562952',
            'lng'   => '-8.770375324893696',
            'title' => 'Leiria'
        ]);

        Office::factory()->create([
            'lat'   => '39.07753883078113',
            'lng'   => '-9.281266331143293',
            'title' => 'Torres Vedras'
        ]);

        $response = $this->getJson(route('api.offices.index', [
            'lat' => '38.720661384644046',
            'lng' => '-9.16044783453807',
        ]));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Torres Vedras')
            ->assertJsonPath('data.1.title', 'Leiria');

        $response = $this->getJson(route('api.offices.index'));

        $response
            ->assertOk()
            ->assertJsonPath('data.0.title', 'Leiria')
            ->assertJsonPath('data.1.title', 'Torres Vedras');
    }

    /**
     * @test
     */
    public function itShowsTheOffice(): void
    {
        $user = User::factory()->create();
        $office = Office::factory()->for($user)->has(Tag::factory())->has(Image::factory())->create();

        Reservation::factory()->for($office)->create();
        Reservation::factory()->for($office)->cancelled()->create();

        $response = $this->getJson(route('api.offices.show', $office));

        $response
            ->assertOk()
            ->assertJsonPath('data.reservations_count', 1)
            ->assertJsonCount(1, 'data.tags')
            ->assertJsonCount(1, 'data.images')
            ->assertJsonPath('data.user.id', $user->id);
    }

    /**
     * @test
     */
    public function itCreatesAnOffice(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $host = User::factory()->create();
        $tags = Tag::factory(2)->create();

        Sanctum::actingAs($host, ['office.create']);

        $response = $this->postJson(route('api.offices.store'), Office::factory()->raw([
            'tags' => $tags->pluck('id')->toArray(),
        ]));

        $response
            ->assertCreated()
            ->assertJsonPath('data.approval_status', OfficeApprovalStatus::Pending->value)
            ->assertJsonPath('data.user.id', $host->id)
            ->assertJsonCount(2, 'data.tags');

        $this
            ->assertDatabaseHas('offices', [
                'id' => $response->json('data.id')
            ]);

        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }

    /**
     * @test
     */
    public function itDoesntAllowCreatingIfScopeIsNotProvided(): void
    {
        $host = User::factory()->create();

        Sanctum::actingAs($host);

        $response = $this->postJson(route('api.offices.store'));

        $response
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function itUpdatesAnOffice(): void
    {
        $host = User::factory()->create();
        $tags = Tag::factory(3)->create();
        $office = Office::factory()->for($host)->create();

        $office->tags()->attach($tags);

        $firstTag = $tags->first();
        $anotherTag = Tag::factory()->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->putJson(route('api.offices.update', $office), [
            'title' => 'Amazing Office',
            'tags'  => [
                $firstTag->id,
                $anotherTag->id,
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonCount(2, 'data.tags')
            ->assertJsonPath('data.tags.0.id', $firstTag->id)
            ->assertJsonPath('data.tags.1.id', $anotherTag->id)
            ->assertJsonPath('data.title', 'Amazing Office');
    }

    /**
     * @test
     */
    public function itDoesntUpdateOfficeThatDoesntBelongToUser(): void
    {
        $host = User::factory()->create();
        $anotherHost = User::factory()->create();
        $office = Office::factory()->for($anotherHost)->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->putJson(route('api.offices.update', $office), [
            'title' => 'Amazing Office',
        ]);

        $response
            ->assertForbidden();
    }

    /**
     * @test
     */
    public function itMarksTheOfficeAsPendingIfDirty(): void
    {
        Notification::fake();

        $admin = User::factory()->admin()->create();
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->putJson(route('api.offices.update', $office), [
            'lat' => 40.74051727562952,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.approval_status', OfficeApprovalStatus::Pending->value);

        $this
            ->assertDatabaseHas('offices', [
                'id'              => $office->id,
                'approval_status' => OfficeApprovalStatus::Pending->value,
            ]);

        Notification::assertSentTo($admin, OfficePendingApproval::class);
    }

    /**
     * @test
     */
    public function itUpdatesTheFeaturedImageOfAnOffice(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $image = Image::factory()->for($office, 'imageable')->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->putJson(route('api.offices.update', $office), [
            'featured_image_id' => $image->id,
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.featured_image.id', $image->id)
            ->assertJsonPath('data.featured_image.url', Storage::url($image->path));
    }

    /**
     * @test
     */
    public function itDoesntUpdateFeaturedImageThatBelongsToAnotherOffice(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $anotherOffice = Office::factory()->for($host)->create();
        $image = Image::factory()->for($anotherOffice, 'imageable')->create();

        Sanctum::actingAs($host, ['office.update']);

        $response = $this->putJson(route('api.offices.update', $office), [
            'featured_image_id' => $image->id,
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('featured_image_id');
    }

    /**
     * @test
     */
    public function itCanDeleteOffices(): void
    {
        Storage::fake();

        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();
        $image = Image::factory()->for($office, 'imageable')->create();

        Storage::put($image->path, 'empty');

        Sanctum::actingAs($host, ['office.delete']);

        $response = $this->deleteJson(route('api.offices.destroy', $office));

        $response
            ->assertNoContent();

        $this
            ->assertSoftDeleted($office)
            ->assertModelMissing($image);

        Storage::assertMissing($image->path);
    }

    /**
     * @test
     */
    public function itCannotDeleteAnOfficeThatHasReservations(): void
    {
        $host = User::factory()->create();
        $office = Office::factory()->for($host)->create();

        Reservation::factory(3)->for($office)->create();

        Sanctum::actingAs($host, ['office.delete']);

        $response = $this->deleteJson(route('api.offices.destroy', $office));

        $response
            ->assertUnprocessable()
            ->assertInvalid('office');
    }
}
