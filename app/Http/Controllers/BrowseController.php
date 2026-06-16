<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\BrowseCache;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrowseController extends Controller
{
    private const PLATFORM_FILTERS = ['youtube', 'netflix'];

    public function __invoke(Request $request): Response
    {
        $promotionSlug = $request->string('promotion', 'wcw')->toString();
        $year = $request->integer('year') ?: null;
        $showType = $request->string('show_type', 'ppv')->toString();
        $watchable = $request->boolean('watchable');
        $platform = $this->resolvePlatformFilter($request->string('platform')->toString());

        $shows = BrowseCache::rememberBrowse($promotionSlug, $showType, $year, $watchable, $platform, function () use ($promotionSlug, $year, $showType, $watchable, $platform) {
            $query = Show::query()
                ->published()
                ->with('promotion')
                ->withCardAggregates()
                ->whereHas('promotion', fn ($q) => $q->where('slug', $promotionSlug))
                ->where('show_type', $showType)
                ->orderByDesc('date');

            if ($year !== null) {
                $query->whereYear('date', $year);
            }

            if ($watchable) {
                $query->watchable();
            }

            if ($platform !== null) {
                $query->withVideoProvider($platform);
            }

            return ShowCardResource::collection($query->get())->resolve();
        });

        $promotions = Promotion::query()->orderBy('name')->get(['id', 'name', 'slug']);
        $years = Show::query()
            ->published()
            ->whereHas('promotion', fn ($q) => $q->where('slug', $promotionSlug))
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM date)::int as year')
            ->orderByDesc('year')
            ->pluck('year');

        return Inertia::render('Browse/Index', [
            'shows' => $shows,
            'promotions' => $promotions,
            'years' => $years,
            'filters' => [
                'promotion' => $promotionSlug,
                'year' => $year,
                'show_type' => $showType,
                'watchable' => $watchable,
                'platform' => $platform,
            ],
        ]);
    }

    private function resolvePlatformFilter(string $platform): ?string
    {
        if ($platform === '' || ! in_array($platform, self::PLATFORM_FILTERS, true)) {
            return null;
        }

        return $platform;
    }
}
