<?php

namespace App\Http\Resources;

use App\Models\Promotion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Promotion */
class PromotionCardResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'logo_path' => $this->logo_path,
            'founded_year' => $this->founded_year,
            'is_active' => $this->is_active,
            'active_years_label' => $this->activeYearsLabel(),
            'status_label' => $this->statusLabel(),
            'headquarters' => $this->headquarters,
            'description' => $this->description,
            'wikipedia_url' => $this->wikipedia_url,
            'published_show_count' => $this->whenCounted('published_show_count'),
        ];
    }
}
