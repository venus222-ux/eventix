<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VenueRequest;
use App\Http\Resources\VenueResource;
use App\Models\Venue;

class VenueController extends Controller
{
    public function index()
    {
        return VenueResource::collection(
            Venue::withCount('events')->orderBy('name')->get()
        );
    }

    public function store(VenueRequest $request)
    {
        return VenueResource::make(Venue::create($request->validated()))
            ->response()->setStatusCode(201);
    }

    public function show(Venue $venue)
    {
        return VenueResource::make($venue->loadCount('events'));
    }

    public function update(VenueRequest $request, Venue $venue)
    {
        $venue->update($request->validated());

        return VenueResource::make($venue);
    }

    public function destroy(Venue $venue)
    {
        if ($venue->events()->withTrashed()->exists()) {
            return response()->json(['message' => 'Venue has events and cannot be deleted'], 409);
        }

        $venue->delete();

        return response()->json(['message' => 'Venue deleted']);
    }
}
