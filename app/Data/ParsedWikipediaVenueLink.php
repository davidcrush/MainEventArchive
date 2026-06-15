<?php

namespace App\Data;

class ParsedWikipediaVenueLink
{
    public function __construct(
        public readonly string $pageTitle,
        public readonly string $displayName,
    ) {}
}
