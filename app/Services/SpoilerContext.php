<?php

namespace App\Services;

use Illuminate\Http\Request;

class SpoilerContext
{
    private bool $enabled = false;

    private ?string $showSlug = null;

    public function resolveFromRequest(Request $request, ?string $showSlug = null): self
    {
        $this->showSlug = $showSlug;
        $this->enabled = $request->boolean('spoilers');

        return $this;
    }

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function showSlug(): ?string
    {
        return $this->showSlug;
    }
}
