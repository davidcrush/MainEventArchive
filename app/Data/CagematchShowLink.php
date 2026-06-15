<?php

namespace App\Data;

use App\Models\Show;

class CagematchShowLink
{
    public function __construct(
        public readonly Show $show,
        public readonly CagematchEvent $event,
        public readonly string $url,
    ) {}
}
