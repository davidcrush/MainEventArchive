<?php

namespace App\Services\YouTube;

use App\Data\YouTubePlaylistEntry;
use App\Models\Show;
use App\Models\Video;

class YouTubeVideoSyncService
{
    /**
     * @return 'created'|'updated'
     */
    public function sync(Show $show, YouTubePlaylistEntry $entry): string
    {
        $existing = Video::query()
            ->where('provider', 'youtube')
            ->where('external_id', $entry->videoId)
            ->first();

        Video::query()->updateOrCreate(
            [
                'provider' => 'youtube',
                'external_id' => $entry->videoId,
            ],
            [
                'show_id' => $show->id,
                'match_id' => null,
                'url' => $this->externalUrl($entry->videoId),
                'title' => $entry->title,
                'is_primary' => true,
            ],
        );

        Video::query()
            ->where('show_id', $show->id)
            ->where('provider', 'youtube')
            ->where('external_id', '!=', $entry->videoId)
            ->update(['is_primary' => false]);

        return $existing === null ? 'created' : 'updated';
    }

    private function externalUrl(string $videoId): string
    {
        return "https://www.youtube.com/watch?v={$videoId}";
    }
}
