<?php

namespace App\Http\Resources;

use App\Models\Show;
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
            'show_type' => $this->show_type->value,
            'promotion' => $this->whenLoaded('promotion', fn () => [
                'name' => $this->promotion->name,
                'slug' => $this->promotion->slug,
            ]),
            'rating_average' => $this->when(isset($this->ratings_avg_stars), round((float) $this->ratings_avg_stars, 1)),
            'rating_count' => $this->when(isset($this->ratings_count), (int) $this->ratings_count),
            'has_video' => (bool) ($this->has_video ?? false),
        ];
    }
}
