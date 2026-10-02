<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Event;
use App\Models\Venue;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;
use App\Services\SeatMapGenerator;
use Illuminate\Support\Facades\File;

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

            $layout = json_decode(File::get(base_path('stubs/seat-layout-example.json')), true);
$generator = app(SeatMapGenerator::class);

foreach ($venues as $venue) {
    if (! $venue->sections()->exists()) {
        $generator->generate($venue, $layout);
    }
}
    }
}
