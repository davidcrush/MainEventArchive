<?php

namespace App\Http\Resources;

use App\Models\Show;
use App\Models\Venue;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Show */
class ShowCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'date' => $this->date->toDateString(),
            'venue' => $this->resolveVenueForResponse(),
            'city' => $this->city,
            'show_type' => $this->show_type->value,
            'promotion' => $this->whenLoaded('promotion', fn () => [
                'name' => $this->promotion->name,
                'slug' => $this->promotion->slug,
            ]),
            'rating_average' => $this->when(isset($this->ratings_avg_stars), round((float) $this->ratings_avg_stars, 1)),
            'rating_count' => $this->when(isset($this->ratings_count), (int) $this->ratings_count),
            'has_video' => (bool) ($this->has_video ?? false),
            'has_card' => (int) ($this->card_match_count ?? 0) > 0,
            'main_event_preview' => $this->when(
                (int) ($this->card_match_count ?? 0) > 0,
                fn () => $this->buildMainEventPreview(),
            ),
        ];
    }

    /**
     * @return array{line: string, title_name: string|null}|null
     */
    private function buildMainEventPreview(): ?array
    {
        if (! $this->relationLoaded('mainEventMatch')) {
            return null;
        }

        $match = $this->mainEventMatch;

        if ($match === null) {
            return null;
        }

        return [
            'line' => $match->spoilerSafePreviewLine(),
            'title_name' => $match->title_name,
        ];
    }

    /**
     * @return array{name: string, slug: string}|string|null
     */
    private function resolveVenueForResponse(): array|string|null
    {
        if ($this->relationLoaded('venue')) {
            $linkedVenue = $this->resource->getRelation('venue');

            if ($linkedVenue instanceof Venue) {
                return [
                    'name' => $linkedVenue->name,
                    'slug' => $linkedVenue->slug,
                ];
            }
        }

        return $this->resource->getAttributes()['venue'] ?? null;
    }
}
