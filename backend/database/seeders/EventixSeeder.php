<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class EventixSeeder extends Seeder
{
    public function run(): void
    {
        $categories = collect(['Concert', 'Theatre', 'Sport', 'Festival', 'Conference'])
            ->map(fn ($name) => Category::firstOrCreate(['slug' => Str::slug($name)], ['name' => $name]));

        $venues = Venue::factory()->count(3)->create();

        Event::factory()->count(12)
            ->recycle($categories)
            ->recycle($venues)
            ->create();
    }
}
