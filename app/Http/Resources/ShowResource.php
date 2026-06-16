<?php

namespace App\Http\Resources;

use App\Models\Show;
use App\Models\Venue;
use App\Services\Streaming\WatchTargetResolver;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Show */
class ShowResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'slug' => $this->slug,
            'date' => $this->date->toDateString(),
            'episode_number' => $this->episode_number,
            'venue' => $this->resolveVenueForResponse(),
            'city' => $this->city,
            'show_type' => $this->show_type->value,
            'tv_rating' => $this->tv_rating !== null ? (float) $this->tv_rating : null,
            'promotion' => $this->whenLoaded('promotion', fn () => [
                'id' => $this->promotion->id,
                'name' => $this->promotion->name,
                'slug' => $this->promotion->slug,
            ]),
            'cagematch_url' => $this->cagematch_url,
            'source' => $this->source,
            'source_id' => $this->source_id,
            'source_url' => $this->source_url,
            'matches' => $this->whenLoaded('matches', function () use ($request) {
                return collect(MatchResource::collection($this->matches)->toArray($request))
                    ->filter(fn (array $match) => $match !== [])
                    ->values()
                    ->all();
            }),
            'rating_average' => $this->when(isset($this->ratings_avg_stars), round((float) $this->ratings_avg_stars, 1)),
            'rating_count' => $this->when(isset($this->ratings_count), (int) $this->ratings_count),
            'on_watchlist' => $this->when(isset($this->on_watchlist), (bool) $this->on_watchlist),
            'is_watched' => $this->when(isset($this->is_watched), (bool) $this->is_watched),
            'watch_targets' => array_map(
                fn ($target) => $target->toArray(),
                app(WatchTargetResolver::class)->resolveAll($this->resource),
            ),
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
