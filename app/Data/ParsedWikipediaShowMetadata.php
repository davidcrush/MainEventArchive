<?php

namespace App\Data;

class ParsedWikipediaShowMetadata
{
    /**
     * @param  list<ParsedWikipediaVenueLink>  $venueLinks
     */
    public function __construct(
        public readonly ?string $venue,
        public readonly ?string $city,
        public readonly ?int $attendance,
        public readonly array $venueLinks = [],
    ) {}
}
