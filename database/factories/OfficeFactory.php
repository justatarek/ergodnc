<?php

namespace Database\Factories;

use App\Enums\OfficeApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Office>
 */
class OfficeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id'          => User::factory(),
            'title'            => fake()->city(),
            'description'      => fake()->paragraph(),
            'lat'              => fake()->latitude(),
            'lng'              => fake()->longitude(),
            'address_line1'    => fake()->address(),
            'approval_status'  => OfficeApprovalStatus::Approved,
            'price_per_day'    => fake()->randomElement([1_000, 2_000, 3_000, 4_000]),
            'monthly_discount' => fake()->boolean(10) ? fake()->numberBetween(10, 90) : 0,
        ];
    }

    public function pending(): Factory
    {
        return $this->state([
            'approval_status' => OfficeApprovalStatus::Pending,
        ]);
    }

    public function hidden(): Factory
    {
        return $this->state([
            'is_hidden' => true,
        ]);
    }
}
