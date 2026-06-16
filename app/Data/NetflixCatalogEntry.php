<?php

namespace App\Data;

class NetflixCatalogEntry
{
    public function __construct(
        public readonly string $titleId,
        public readonly string $title,
    ) {}
}
