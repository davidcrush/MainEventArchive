<?php

namespace App\Data;

class ParsedWikipediaMatch
{
    /**
     * @param  list<array{name: string, side: int, sort_order: int}>  $participants
     * @param  list<string>  $entrantNames
     */
    public function __construct(
        public readonly int $cardOrder,
        public readonly string $matchType,
        public readonly ?string $titleName,
        public readonly array $participants,
        public readonly ?int $winnerSide,
        public readonly ?string $finish,
        public readonly ?int $durationSeconds,
        public readonly bool $isRateable = true,
        public readonly bool $isPpv = true,
        public readonly array $entrantNames = [],
    ) {}
}
