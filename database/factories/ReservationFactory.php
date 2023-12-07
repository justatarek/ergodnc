<?php

namespace Database\Factories;

use App\Enums\ReservationStatus;
use App\Models\Office;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'    => User::factory(),
            'office_id'  => Office::factory(),
            'price'      => fake()->numberBetween(10_000, 20_000),
            'start_date' => now()->addDay()->format('Y-m-d'),
            'end_date'   => now()->addDays(5)->format('Y-m-d'),
        ];
    }

    public function cancelled(): static
    {
        return $this->state([
            'status' => ReservationStatus::Canceled,
        ]);
    }
}
