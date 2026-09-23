<?php

namespace Database\Factories;

use App\Enums\EventStatus;
use App\Models\Category;
use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class EventFactory extends Factory
{
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('+1 week', '+6 months');

        return [
            'title' => fake()->sentence(3),
            'slug' => fn (array $attrs) => Str::slug($attrs['title']).'-'.fake()->unique()->numberBetween(1, 999999),
            'description' => fake()->paragraph(),
            'category_id' => Category::factory(),
            'venue_id' => Venue::factory(),
            'starts_at' => $start,
            'ends_at' => (clone $start)->modify('+3 hours'),
            'status' => EventStatus::Published,
        ];
    }

    public function draft(): static
    {
        return $this->state(['status' => EventStatus::Draft]);
    }

    public function cancelled(): static
    {
        return $this->state(['status' => EventStatus::Cancelled]);
    }
}
