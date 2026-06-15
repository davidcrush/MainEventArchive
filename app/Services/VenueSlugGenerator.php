<?php

namespace App\Services;

use App\Models\Venue;
use Illuminate\Support\Str;

class VenueSlugGenerator
{
    public function generate(string $name, ?int $excludeVenueId = null): string
    {
        $baseSlug = Str::slug($name);

        if ($baseSlug === '') {
            $baseSlug = 'venue';
        }

        if (! $this->slugExists($baseSlug, $excludeVenueId)) {
            return $baseSlug;
        }

        $suffix = 2;

        while ($this->slugExists("{$baseSlug}-{$suffix}", $excludeVenueId)) {
            $suffix++;
        }

        return "{$baseSlug}-{$suffix}";
    }

    private function slugExists(string $slug, ?int $excludeVenueId): bool
    {
        $query = Venue::query()->where('slug', $slug);

        if ($excludeVenueId !== null) {
            $query->where('id', '!=', $excludeVenueId);
        }

        return $query->exists();
    }
}
