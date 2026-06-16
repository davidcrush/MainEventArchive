<?php

namespace App\Data;

class WatchTarget
{
    public function __construct(
        public readonly string $provider,
        public readonly string $url,
        public readonly string $mode,
        public readonly string $label,
    ) {}

    /**
     * @return array{provider: string, url: string, mode: string, label: string}
     */
    public function toArray(): array
    {
        return [
            'provider' => $this->provider,
            'url' => $this->url,
            'mode' => $this->mode,
            'label' => $this->label,
        ];
    }
}
