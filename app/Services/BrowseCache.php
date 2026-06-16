<?php

namespace App\Services;

use Closure;
use Illuminate\Support\Facades\Cache;

class BrowseCache
{
    private const VERSION_KEY = 'browse.cache_version';

    private const TTL_SECONDS = 300;

    public static function rememberBrowse(
        string $promotionSlug,
        string $showType,
        ?int $year,
        bool $watchable,
        ?string $platform,
        int $page,
        Closure $callback,
    ): mixed {
        return Cache::remember(
            self::browseKey($promotionSlug, $showType, $year, $watchable, $platform, $page),
            self::TTL_SECONDS,
            $callback,
        );
    }

    public static function rememberFeaturedShows(Closure $callback): mixed
    {
        return Cache::remember(
            self::featuredShowsKey(),
            self::TTL_SECONDS,
            $callback,
        );
    }

    public static function invalidate(): void
    {
        $version = (int) Cache::get(self::VERSION_KEY, 1);

        Cache::forever(self::VERSION_KEY, $version + 1);
    }

    public static function browseKey(
        string $promotionSlug,
        string $showType,
        ?int $year,
        bool $watchable,
        ?string $platform = null,
        int $page = 1,
    ): string {
        return sprintf(
            'browse.v%d.%s.%s.%s.%s.%s.page.%d',
            self::version(),
            $promotionSlug,
            $showType,
            $year ?? 'all',
            $watchable ? 'watchable' : 'all',
            $platform ?? 'all',
            $page,
        );
    }

    private static function featuredShowsKey(): string
    {
        return 'home.featured_shows.v'.self::version();
    }

    private static function version(): int
    {
        return (int) Cache::get(self::VERSION_KEY, 1);
    }
}
