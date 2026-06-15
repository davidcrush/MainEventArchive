<?php

namespace App\Data;

class YouTubePlaylistEntry
{
    public function __construct(
        public readonly string $videoId,
        public readonly string $title,
        public readonly ?int $durationSeconds = null,
    ) {}
}
