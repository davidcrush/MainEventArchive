<?php

namespace App\Services\Streaming;

use App\Data\WatchTarget;
use App\Models\Show;
use App\Models\Video;

class WatchTargetResolver
{
    public function __construct(
        private NetflixWatchUrlResolver $netflixWatchUrlResolver,
    ) {}

    /**
     * @return list<WatchTarget>
     */
    public function resolveAll(Show $show): array
    {
        $targets = [];

        $youtubeTarget = $this->resolveYoutubeTarget($show);

        if ($youtubeTarget !== null) {
            $targets[] = $youtubeTarget;
        }

        $netflixTarget = $this->netflixWatchUrlResolver->resolve($show);

        if ($netflixTarget !== null) {
            $targets[] = $netflixTarget;
        }

        return $targets;
    }

    private function resolveYoutubeTarget(Show $show): ?WatchTarget
    {
        $show->loadMissing('videos');

        $video = $show->videos
            ->whereNull('match_id')
            ->where('provider', 'youtube')
            ->sortByDesc('is_primary')
            ->sortBy('id')
            ->first();

        if (! $video instanceof Video) {
            return null;
        }

        return new WatchTarget(
            provider: 'youtube',
            url: $video->url,
            mode: 'external',
            label: 'Watch on YouTube',
        );
    }
}
