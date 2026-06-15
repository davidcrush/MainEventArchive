<?php

namespace App\Services\YouTube;

use App\Data\YouTubePlaylistEntry;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YouTubePlaylistClient
{
    public function __construct(
        private YouTubeTitleParser $titleParser,
    ) {}

    /**
     * @return list<YouTubePlaylistEntry>
     */
    public function fetchPlaylistItems(string $playlistId, bool $includeFullEpisodes = false): array
    {
        $apiKey = config('youtube.api_key');

        if (! is_string($apiKey) || $apiKey === '') {
            throw new RuntimeException('YOUTUBE_API_KEY is not configured.');
        }

        $entries = [];
        $pageToken = null;

        do {
            $response = Http::timeout(30)->get('https://www.googleapis.com/youtube/v3/playlistItems', array_filter([
                'part' => 'snippet',
                'playlistId' => $playlistId,
                'maxResults' => 50,
                'pageToken' => $pageToken,
                'key' => $apiKey,
            ]));

            if (! $response->successful()) {
                throw new RuntimeException(sprintf(
                    'YouTube API request failed (%s): %s',
                    $response->status(),
                    $response->json('error.message') ?? $response->body(),
                ));
            }

            /** @var list<array<string, mixed>> $items */
            $items = $response->json('items') ?? [];

            foreach ($items as $item) {
                $snippet = $item['snippet'] ?? [];
                $videoId = $snippet['resourceId']['videoId'] ?? null;
                $title = $snippet['title'] ?? '';

                if (! is_string($videoId) || $videoId === '') {
                    continue;
                }

                if (! is_string($title) || $title === '' || in_array($title, ['Private video', 'Deleted video'], true)) {
                    continue;
                }

                if (! $this->titleParser->isSyncableTitle($title, $includeFullEpisodes)) {
                    continue;
                }

                $entries[$videoId] = new YouTubePlaylistEntry($videoId, $title);
            }

            $pageToken = $response->json('nextPageToken');
        } while (is_string($pageToken) && $pageToken !== '');

        return array_values($entries);
    }
}
