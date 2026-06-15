<?php

namespace App\Data;

use Carbon\CarbonInterface;

class CagematchEvent
{
    public function __construct(
        public readonly int $eventId,
        public readonly string $title,
        public readonly CarbonInterface $date,
    ) {}
}
