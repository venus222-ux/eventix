<?php

namespace App\Services;

use App\Enums\SeatStatus;
use App\Models\Section;
use App\Models\Seat;
use App\Models\Venue;
use Illuminate\Support\Facades\DB;

class SeatMapGenerator
{
    /**
     * @param  array<int, array{name:string, rows:int, seats_per_row:int, price_cents:int}>  $sections
     * @return int total seats generated
     */
    public function generate(Venue $venue, array $sections): int
    {
        return DB::transaction(function () use ($venue, $sections) {
            $total = 0;

            foreach ($sections as $def) {
                $section = Section::create([
                    'venue_id' => $venue->id,
                    'name' => $def['name'],
                    'price_cents' => $def['price_cents'],
                ]);

                $rows = [];
                $now = now();
                foreach ($this->rowLabels($def['rows']) as $rowLabel) {
                    for ($number = 1; $number <= $def['seats_per_row']; $number++) {
                        $rows[] = [
                            'section_id' => $section->id,
                            'row' => $rowLabel,
                            'number' => $number,
                            'status' => SeatStatus::Available->value,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                // bulk insert in chunks instead of one INSERT per seat
                foreach (array_chunk($rows, 500) as $chunk) {
                    Seat::insert($chunk);
                }

                $total += count($rows);
            }

            return $total;
        });
    }

    /** A, B, ... Z, AA, AB, ... (spreadsheet-style row labels) */
    private function rowLabels(int $rows): array
    {
        $labels = [];
        for ($i = 0; $i < $rows; $i++) {
            $label = '';
            $n = $i;
            do {
                $label = chr(65 + ($n % 26)).$label;
                $n = intdiv($n, 26) - 1;
            } while ($n >= 0);
            $labels[] = $label;
        }

        return $labels;
    }
}
