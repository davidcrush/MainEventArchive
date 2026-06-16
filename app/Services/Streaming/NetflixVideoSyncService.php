<?php

namespace App\Services\Streaming;

use App\Data\NetflixCatalogEntry;
use App\Models\Show;
use App\Models\Video;

class NetflixVideoSyncService
{
    public function __construct(
        private NetflixUrlParser $urlParser,
    ) {}

    /**
     * @return 'created'|'updated'|'skipped'
     */
    public function sync(Show $show, NetflixCatalogEntry $entry, bool $force = false): string
    {
        $reference = $this->urlParser->parse($entry->titleId);

        $existingForShow = Video::query()
            ->where('show_id', $show->id)
            ->where('provider', 'netflix')
            ->whereNull('match_id')
            ->first();

        if ($existingForShow !== null && ! $force && $existingForShow->external_id !== $reference['external_id']) {
            return 'skipped';
        }

        $existingByExternalId = Video::query()
            ->where('provider', 'netflix')
            ->where('external_id', $reference['external_id'])
            ->first();

        Video::query()->updateOrCreate(
            [
                'provider' => 'netflix',
                'external_id' => $reference['external_id'],
            ],
            [
                'show_id' => $show->id,
                'match_id' => null,
                'url' => $reference['url'],
                'title' => $entry->title,
                'is_primary' => true,
                'embeddable' => false,
                'last_verified_at' => now(),
            ],
        );

        Video::query()
            ->where('show_id', $show->id)
            ->where('provider', 'netflix')
            ->where('external_id', '!=', $reference['external_id'])
            ->update(['is_primary' => false]);

        return $existingByExternalId === null ? 'created' : 'updated';
    }
}
