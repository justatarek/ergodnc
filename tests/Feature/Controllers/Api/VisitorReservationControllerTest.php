<?php

namespace Tests\Feature\Controllers\Api;

use App\Enums\ReservationStatus;
use App\Models\Image;
use App\Models\Office;
use App\Models\Reservation;
use App\Models\User;
use App\Notifications\NewHostReservation;
use App\Notifications\NewVisitorReservation;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class VisitorReservationControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @test
     */
    public function itListsReservationsThatBelongToTheUser(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();
        $image = Image::factory()->for($office, 'imageable')->create();

        $office->update([
            'featured_image_id' => $image->id,
        ]);

        Reservation::factory(2)->for($visitor)->for($office)->create();
        Reservation::factory(3)->create();

        Sanctum::actingAs($visitor, ['reservation.viewAny']);

        $response = $this->getJson(route('api.reservations.visitor.index'));

        $response
            ->assertOk()
            ->assertJsonStructure(['data', 'meta', 'links'])
            ->assertJsonCount(2, 'data')
            ->assertJsonStructure(['data' => ['*' => ['id', 'office']]])
            ->assertJsonPath('data.0.office.featured_image.url', Storage::url($image->path));
    }

    /**
     * @test
     */
    public function itListsReservationFilteredByDateRange(): void
    {
        $visitor = User::factory()->create();

        $fromDate = '2021-03-03';
        $toDate = '2021-04-04';

        // Within the date range
        $reservations = Reservation::factory()->for($visitor)->createMany([
            [
                'start_date' => '2021-03-01',
                'end_date'   => '2021-03-15',
            ],
            [
                'start_date' => '2021-03-25',
                'end_date'   => '2021-04-15',
            ],
            [
                'start_date' => '2021-03-25',
                'end_date'   => '2021-03-29',
            ],
            [
                'start_date' => '2021-03-01',
                'end_date'   => '2021-04-15',
            ],
        ]);

        // Within the range but belongs to a different user
        Reservation::factory()->create([
            'start_date' => '2021-03-25',
            'end_date'   => '2021-03-29',
        ]);

        // Outside the date range
        Reservation::factory()->for($visitor)->createMany([
            [
                'start_date' => '2021-02-25',
                'end_date'   => '2021-03-01',
            ],
            [
                'start_date' => '2021-05-01',
                'end_date'   => '2021-05-01',
            ],
        ]);

        Sanctum::actingAs($visitor, ['reservation.viewAny']);

        $response = $this->getJson(route('api.reservations.visitor.index', [
            'fromDate' => $fromDate,
            'toDate'   => $toDate,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(4, 'data');

        $this
            ->assertEquals($reservations->pluck('id')->toArray(), collect($response->json('data'))->pluck('id')->toArray());
    }

    /**
     * @test
     */
    public function itFiltersResultsByStatus(): void
    {
        $visitor = User::factory()->create();
        $reservation = Reservation::factory()->for($visitor)->create();
        Reservation::factory()->for($visitor)->cancelled()->create();

        Sanctum::actingAs($visitor, ['reservation.viewAny']);

        $response = $this->getJson(route('api.reservations.visitor.index', [
            'status' => ReservationStatus::Active,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation->id);
    }

    /**
     * @test
     */
    public function itFiltersResultsByOffice(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();
        $reservation = Reservation::factory()->for($office)->for($visitor)->create();
        Reservation::factory()->for($visitor)->create();

        Sanctum::actingAs($visitor, ['reservation.viewAny']);

        $response = $this->getJson(route('api.reservations.visitor.index', [
            'officeId' => $office->id,
        ]));

        $response
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.id', $reservation->id);
    }

    /**
     * @test
     */
    public function itMakesReservations(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create([
            'price_per_day'    => 1_000,
            'monthly_discount' => 10,
        ]);

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(40)->toDateString(),
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.price', 36000)
            ->assertJsonPath('data.user_id', $visitor->id)
            ->assertJsonPath('data.office_id', $office->id)
            ->assertJsonPath('data.status', ReservationStatus::Active->value);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnNonExistingOffice(): void
    {
        $visitor = User::factory()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => 10000,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(40)->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('office_id')
            ->assertJsonValidationErrors(['office_id' => 'Invalid office_id']);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnOfficeThatBelongsToTheUser(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->for($visitor)->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(40)->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('office_id')
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on your own office']);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnOfficeThatIsPendingOrHidden(): void
    {
        $visitor = User::factory()->create();
        $pendingOffice = Office::factory()->pending()->create();
        $hiddenOffice = Office::factory()->hidden()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $pendingOffice->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(40)->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('office_id')
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $hiddenOffice->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(40)->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('office_id')
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation on a hidden office']);
    }

    /**
     * @test
     */
    public function itCannotMakeReservationLessThan2Days(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDay()->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('end_date');
    }

    /**
     * @test
     */
    public function itCannotMakeReservationOnSameDay(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->toDateString(),
            'end_date'   => now()->addDays(3)->toDateString(),
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('start_date');
    }

    /**
     * @test
     */
    public function itMakeReservationFor2Days(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
        ]);

        $response
            ->assertCreated();
    }

    /**
     * @test
     */
    public function itCannotMakeReservationThatsConflicting(): void
    {
        $visitor = User::factory()->create();
        $office = Office::factory()->create();

        $fromDate = now()->addDays(2)->toDateString();
        $toDate = now()->addDay(15)->toDateString();

        Reservation::factory()->for($office)->create([
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => $toDate,
        ]);

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => $fromDate,
            'end_date'   => $toDate,
        ]);

        $response
            ->assertUnprocessable()
            ->assertInvalid('office_id')
            ->assertJsonValidationErrors(['office_id' => 'You cannot make a reservation during this time']);
    }

    /**
     * @test
     */
    public function itSendsNotificationsOnNewReservations(): void
    {
        Notification::fake();

        $visitor = User::factory()->create();
        $office = Office::factory()->create();

        Sanctum::actingAs($visitor, ['reservation.create']);

        $response = $this->postJson(route('api.reservations.visitor.store'), [
            'office_id'  => $office->id,
            'start_date' => now()->addDay()->toDateString(),
            'end_date'   => now()->addDays(2)->toDateString(),
        ]);

        $response
            ->assertCreated();

        Notification::assertSentTo($visitor, NewVisitorReservation::class);
        Notification::assertSentTo($office->user, NewHostReservation::class);
    }
}
