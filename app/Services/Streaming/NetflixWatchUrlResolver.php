<?php

namespace App\Services\Streaming;

use App\Data\WatchTarget;
use App\Models\Show;

class NetflixWatchUrlResolver
{
    public function resolve(Show $show): ?WatchTarget
    {
        if (! $show->isWwePpvNetflixSearchEligible()) {
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
