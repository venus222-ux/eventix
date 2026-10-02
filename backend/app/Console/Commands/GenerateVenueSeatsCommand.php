<?php

namespace App\Console\Commands;

use App\Models\Venue;
use App\Services\SeatMapGenerator;
use Illuminate\Console\Command;

class GenerateVenueSeatsCommand extends Command
{
    protected $signature = 'venue:generate-seats {venue_id} {--layout= : Path to a JSON layout file}';

    protected $description = 'Generate sections and seats for a venue from a JSON layout';

    public function handle(SeatMapGenerator $generator): int
    {
        $venue = Venue::find($this->argument('venue_id'));

        if (! $venue) {
            $this->error('Venue not found.');

            return self::FAILURE;
        }

        if ($venue->sections()->exists()) {
            $this->error('This venue already has sections. Delete them first if you want to regenerate.');

            return self::FAILURE;
        }

        $path = $this->option('layout') ?: base_path('stubs/seat-layout-example.json');

        if (! file_exists($path)) {
            $this->error("Layout file not found: {$path}");

            return self::FAILURE;
        }

        $sections = json_decode(file_get_contents($path), true);

        if (json_last_error() !== JSON_ERROR_NONE || ! is_array($sections)) {
            $this->error('Layout file is not valid JSON.');

            return self::FAILURE;
        }

        $total = $generator->generate($venue, $sections);

        $this->info("Generated {$total} seats across ".count($sections)." section(s) for venue #{$venue->id}.");

        return self::SUCCESS;
    }
}
