<?php

namespace Database\Factories;

use App\Enums\SeatStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

class SeatFactory extends Factory
{
    public function definition(): array
    {
        return [
            'row' => fake()->randomLetter(),
            'number' => fake()->unique()->numberBetween(1, 5000),
            'status' => SeatStatus::Available,
        ];
    }
}
