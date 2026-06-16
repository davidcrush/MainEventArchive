<?php

namespace App\Data;

class ImportRequest
{
    public function __construct(
        public readonly string $source,
        public readonly int $fromYear,
        public readonly int $toYear,
        public readonly ?string $identifier = null,
        public readonly ?string $promotionSlug = null,
        public readonly ?int $chunkIndex = null,
        public readonly ?int $chunkTotal = null,
    ) {}
}
