<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Models\Show;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $featuredShows = Cache::remember('home.featured_shows', 300, function () {
            $shows = Show::query()
                ->published()
                ->with('promotion')
                ->withCardAggregates()
                ->orderByDesc('date')
                ->limit(8)
                ->get();

            return ShowCardResource::collection($shows)->resolve();
        });

        return Inertia::render('Home', [
            'featuredShows' => $featuredShows,
        ]);
    }
}
