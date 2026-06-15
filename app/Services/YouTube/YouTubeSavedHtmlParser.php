<?php

namespace App\Services\YouTube;

use App\Data\YouTubePlaylistEntry;

class YouTubeSavedHtmlParser
{
    public function __construct(
        private YouTubeTitleParser $titleParser,
    ) {}

    /**
     * @return list<YouTubePlaylistEntry>
     */
    public function parse(string $html, bool $includeFullEpisodes = false): array
    {
        $pattern = '/id="video-title"[^>]+title="([^"]+)"[^>]+href="https:\/\/www\.youtube\.com\/watch\?v=([A-Za-z0-9_-]{11})/';

        if (preg_match_all($pattern, $html, $matches, PREG_SET_ORDER) === 0) {
            return [];
        }

        $entries = [];

        foreach ($matches as $match) {
            $title = html_entity_decode($match[1], ENT_QUOTES | ENT_HTML5);
            $videoId = $match[2];

            if (! $this->titleParser->isSyncableTitle($title, $includeFullEpisodes)) {
                continue;
            }

            $entries[$videoId] = new YouTubePlaylistEntry($videoId, $title);
        }

        return array_values($entries);
    }
}
