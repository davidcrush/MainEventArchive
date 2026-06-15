<?php

namespace App\Data;

class ResolvedWikipediaPage
{
    public function __construct(
        public readonly string $canonicalTitle,
        public readonly ?string $redirectFrom,
        public readonly string $wikitext,
    ) {}
}
