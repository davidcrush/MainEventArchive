<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowResource;
use App\Models\Show;
use App\Services\SpoilerContext;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ShowController extends Controller
{
    public function show(Request $request, string $slug): Response
    {
        app(SpoilerContext::class)->resolveFromRequest($request, $slug);

        $show = Show::query()
            ->published()
            ->where('slug', $slug)
            ->with([
                'promotion',
                'venue',
                'videos' => fn ($query) => $query
                    ->whereNull('match_id')
                    ->orderByDesc('is_primary')
                    ->orderBy('id'),
                'matches' => fn ($query) => $query
                    ->where('is_ppv', true)
                    ->with(['participants'])
                    ->withAvg('ratings', 'stars')
                    ->withCount('ratings'),
            ])
            ->withAvg('ratings', 'stars')
            ->withCount('ratings')
            ->firstOrFail();

        if ($user = $request->user()) {
            $show->on_watchlist = $user->watchlistItems()->where('show_id', $show->id)->exists();
            $show->is_watched = $user->watchedShows()->where('show_id', $show->id)->exists();
        }

        return Inertia::render('Shows/Show', [
            'show' => (new ShowResource($show))->resolve(),
            'spoilersEnabled' => app(SpoilerContext::class)->isEnabled(),
        ]);
    }
}
