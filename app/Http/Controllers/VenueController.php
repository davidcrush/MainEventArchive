<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Http\Resources\VenueResource;
use App\Models\Venue;
use Inertia\Inertia;
use Inertia\Response;

class VenueController extends Controller
{
    public function show(string $slug): Response
    {
        $venue = Venue::query()
            ->where('slug', $slug)
            ->with(['aliases' => fn ($query) => $query->orderBy('name')])
            ->firstOrFail();

        $shows = $venue->shows()
            ->published()
            ->with('promotion')
            ->withCardAggregates()
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Venues/Show', [
            'venue' => (new VenueResource($venue))->resolve(),
            'shows' => ShowCardResource::collection($shows)->resolve(),
        ]);
    }
}
