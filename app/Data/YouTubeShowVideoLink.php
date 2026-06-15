<?php

namespace App\Data;

use App\Models\Show;

class YouTubeShowVideoLink
{
    public function __construct(
        public readonly Show $show,
        public readonly YouTubePlaylistEntry $entry,
    ) {}
}
