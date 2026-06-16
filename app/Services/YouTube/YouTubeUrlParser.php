<?php

namespace App\Services\YouTube;

use InvalidArgumentException;

class YouTubeUrlParser
{
    /**
     * @return array{external_id: string, url: string}
     */
    public function parse(string $input): array
    {
        $input = trim($input);

        if ($input === '') {
            throw new InvalidArgumentException('YouTube URL or video ID is required.');
        }

        if (preg_match('/(?:youtube\.com\/(?:watch\?.*v=|embed\/|shorts\/)|youtu\.be\/)([A-Za-z0-9_-]{11})/', $input, $matches) === 1) {
            return $this->buildReference($matches[1]);
        }

        if (preg_match('/^[A-Za-z0-9_-]{11}$/', $input) === 1) {
            return $this->buildReference($input);
        }

        throw new InvalidArgumentException('Could not parse YouTube video ID from input.');
    }

    /**
     * @return array{external_id: string, url: string}
     */
    private function buildReference(string $videoId): array
    {
        return [
            'external_id' => $videoId,
            'url' => "https://www.youtube.com/watch?v={$videoId}",
        ];
    }
}
