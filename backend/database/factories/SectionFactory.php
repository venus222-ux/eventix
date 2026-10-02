<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['VIP', 'Categoria 1', 'Categoria 2', 'General']),
            'price_cents' => fake()->randomElement([10000, 15000, 25000, 50000]),
        ];
    }
}
