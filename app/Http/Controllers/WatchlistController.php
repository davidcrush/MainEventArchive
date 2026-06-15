<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Models\Show;
use App\Models\WatchlistItem;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WatchlistController extends Controller
{
    public function index(Request $request): Response
    {
        $shows = Show::query()
            ->published()
            ->whereHas('watchlistItems', fn ($q) => $q->where('user_id', $request->user()->id))
            ->with('promotion')
            ->withCardAggregates()
            ->orderByDesc('date')
            ->get();

        return Inertia::render('Watchlist/Index', [
            'shows' => ShowCardResource::collection($shows)->resolve(),
        ]);
    }

    public function store(Request $request, Show $show): RedirectResponse
    {
        WatchlistItem::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'show_id' => $show->id,
        ]);

        return back();
    }

    public function destroy(Request $request, Show $show): RedirectResponse
    {
        WatchlistItem::query()
            ->where('user_id', $request->user()->id)
            ->where('show_id', $show->id)
            ->delete();

        return back();
    }
}
