<?php

namespace App\Data;

class ImportResult
{
    /**
     * @param  list<string>  $warnings
     */
    public function __construct(
        public readonly int $created = 0,
        public readonly int $updated = 0,
        public readonly int $skipped = 0,
        public readonly array $warnings = [],
    ) {}
}
