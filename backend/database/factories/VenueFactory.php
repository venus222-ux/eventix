<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class VenueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->company().' Arena',
            'city' => fake()->city(),
            'address' => fake()->streetAddress(),
            'capacity' => fake()->randomElement([500, 1500, 5000, 12000, 30000]),
        ];
    }
}
