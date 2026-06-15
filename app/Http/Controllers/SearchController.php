<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Models\Show;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SearchController extends Controller
{
    public function __invoke(Request $request): Response
    {
        $query = trim($request->string('q')->toString());

        $shows = collect();

        if ($query !== '') {
            $shows = Show::query()
                ->published()
                ->with('promotion')
                ->withCardAggregates()
                ->where(function ($builder) use ($query) {
                    $builder->whereRaw("title_search @@ plainto_tsquery('english', ?)", [$query])
                        ->orWhereHas('matches.participants', function ($participantQuery) use ($query) {
                            $participantQuery->whereRaw("name_search @@ plainto_tsquery('english', ?)", [$query]);
                        });
                })
                ->orderByDesc('date')
                ->limit(50)
                ->get();
        }

        return Inertia::render('Search/Index', [
            'query' => $query,
            'shows' => ShowCardResource::collection($shows)->resolve(),
        ]);
    }
}
