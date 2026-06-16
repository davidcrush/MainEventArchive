<?php

namespace App\Http\Controllers;

use App\Http\Resources\ShowCardResource;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\BrowseCache;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class BrowseController extends Controller
{
    private const ALL_PROMOTIONS = 'all';

    private const PLATFORM_FILTERS = ['youtube', 'netflix'];

    public function __invoke(Request $request): Response
    {
        $promotionSlug = $request->string('promotion', 'wcw')->toString();
        $year = $request->integer('year') ?: null;
        $showType = $request->string('show_type', 'ppv')->toString();
        $watchable = $request->boolean('watchable');
        $platform = $this->resolvePlatformFilter($request->string('platform')->toString());
        $page = max(1, $request->integer('page', 1));
        $perPage = config('catalog.browse_per_page', 20);

        $shows = BrowseCache::rememberBrowse(
            $promotionSlug,
            $showType,
            $year,
            $watchable,
            $platform,
            $page,
            function () use ($promotionSlug, $year, $showType, $watchable, $platform, $page, $perPage) {
                $query = $this->buildBrowseQuery($promotionSlug, $showType, $year, $watchable, $platform);

                $paginator = $query
                    ->paginate($perPage, ['*'], 'page', $page)
                    ->withQueryString();

                return ShowCardResource::collection($paginator)->response()->getData(true);
            },
        );

        $promotions = Promotion::query()
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name', 'slug']);

        $years = $this->buildYearsQuery($promotionSlug, $showType)
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

    /**
     * @return Builder<Show>
     */
    private function buildBrowseQuery(
        string $promotionSlug,
        string $showType,
        ?int $year = null,
        bool $watchable = false,
        ?string $platform = null,
    ): Builder {
        $query = Show::query()
            ->published()
            ->with('promotion')
            ->withCardAggregates()
            ->where('show_type', $showType)
            ->orderByDesc('date');

        if ($promotionSlug !== self::ALL_PROMOTIONS) {
            $query->whereHas('promotion', fn ($q) => $q->where('slug', $promotionSlug));
        }

        if ($year !== null) {
            $query->whereYear('date', $year);
        }

        if ($watchable) {
            $query->watchable();
        }

        if ($platform !== null) {
            $query->withVideoProvider($platform);
        }

        return $query;
    }

    /**
     * @return Builder<Show>
     */
    private function buildYearsQuery(string $promotionSlug, string $showType): Builder
    {
        $query = Show::query()
            ->published()
            ->where('show_type', $showType)
            ->selectRaw('DISTINCT EXTRACT(YEAR FROM date)::int as year')
            ->orderByDesc('year');

        if ($promotionSlug !== self::ALL_PROMOTIONS) {
            $query->whereHas('promotion', fn ($q) => $q->where('slug', $promotionSlug));
        }

        return $query;
    }

    private function resolvePlatformFilter(string $platform): ?string
    {
        if ($platform === '' || ! in_array($platform, self::PLATFORM_FILTERS, true)) {
            return null;
        }

        return $platform;
    }
}
