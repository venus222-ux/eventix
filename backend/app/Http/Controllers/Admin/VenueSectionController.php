<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Section;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VenueSectionController extends Controller
{
    // GET /api/admin/venues/{venue}/sections?event_id=12
    public function index(Request $request, int $venue): JsonResponse
    {
        $overrides = [];

        if ($request->filled('event_id')) {
            $event = Event::withTrashed()->find($request->integer('event_id'));

            if ($event && (int) $event->venue_id === $venue) {
                $overrides = $event->sections()->pluck('event_section_prices.price_cents', 'sections.id')->all();
            }
        }

        $data = Section::where('venue_id', $venue)
            ->orderBy('name')
            ->get(['id', 'name', 'price_cents'])
            ->map(fn (Section $s) => [
                'id' => $s->id,
                'name' => $s->name,
                'default_price_cents' => (int) $s->price_cents,
                'event_price_cents' => $overrides[$s->id] ?? null,
            ]);

        return response()->json(['data' => $data]);
    }
}