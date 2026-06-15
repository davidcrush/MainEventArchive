<?php

namespace App\Http\Resources;

use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Venue */
class VenueResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'city' => $this->city,
            'state_province' => $this->state_province,
            'country' => $this->country,
            'location' => $this->formatLocation(),
            'capacity' => $this->capacity,
            'wikipedia_url' => $this->wikipedia_url,
            'aliases' => $this->whenLoaded('aliases', function () {
                return $this->aliases
                    ->filter(fn ($alias) => $alias->name !== $this->name)
                    ->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)
                    ->values()
                    ->map(fn ($alias) => ['name' => $alias->name])
                    ->all();
            }),
        ];
    }

    private function formatLocation(): ?string
    {
        $parts = array_filter([
            $this->city,
            $this->state_province,
            $this->country,
        ], fn (?string $part) => $part !== null && $part !== '');

        if ($parts === []) {
            return null;
        }

        return implode(', ', $parts);
    }
}
