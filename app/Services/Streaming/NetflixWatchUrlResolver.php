<?php

namespace App\Services\Streaming;

use App\Data\WatchTarget;
use App\Enums\ShowType;
use App\Models\Show;
use App\Models\Video;

class NetflixWatchUrlResolver
{
    public function resolve(Show $show): ?WatchTarget
    {
        $deepLink = $this->resolveDeepLink($show);

        if ($deepLink !== null) {
            return $deepLink;
        }

        return $this->resolveSearchFallback($show);
    }

    private function resolveDeepLink(Show $show): ?WatchTarget
    {
        $show->loadMissing('videos');

        $video = $show->videos
            ->whereNull('match_id')
            ->where('provider', 'netflix')
            ->sortByDesc('is_primary')
            ->sortBy('id')
            ->first();

        if (! $video instanceof Video) {
            return null;
        }

        return new WatchTarget(
            provider: 'netflix',
            url: $video->url,
            mode: 'deep_link',
            label: 'Watch on Netflix',
        );
    }

    private function resolveSearchFallback(Show $show): ?WatchTarget
    {
        if (! config('streaming.netflix.wwe_ppv_search_enabled', true)) {
            return null;
        }

        $show->loadMissing('promotion');

        if ($show->promotion?->slug !== 'wwe' || $show->show_type !== ShowType::Ppv) {
            return null;
        }

        $template = config('streaming.netflix.search_url_template');

        if (! is_string($template) || $template === '') {
            return null;
        }

        return new WatchTarget(
            provider: 'netflix',
            url: sprintf($template, rawurlencode($show->title)),
            mode: 'search',
            label: 'Watch on Netflix',
        );
    }
}
