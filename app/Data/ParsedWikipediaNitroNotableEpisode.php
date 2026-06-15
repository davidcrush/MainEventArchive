<?php

namespace App\Data;

use Carbon\CarbonInterface;

readonly class ParsedWikipediaNitroNotableEpisode
{
    public function __construct(
        public string $episodeTitle,
        public CarbonInterface $date,
        public ?string $venue,
        public ?string $city,
        public ?float $tvRating,
        public ?string $note,
    ) {}
}
