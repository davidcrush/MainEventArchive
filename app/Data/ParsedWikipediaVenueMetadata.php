<?php

namespace App\Data;

class ParsedWikipediaVenueMetadata
{
    /**
     * @param  list<string>  $formerNames
     */
    public function __construct(
        public readonly ?string $name,
        public readonly ?string $city,
        public readonly ?string $stateProvince,
        public readonly ?string $country,
        public readonly ?int $capacity,
        public readonly array $formerNames = [],
    ) {}
}
