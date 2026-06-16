<?php

namespace App\Models;

use Database\Factories\PromotionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'logo_path',
    'founded_year',
    'active_from_year',
    'active_to_year',
    'is_active',
    'headquarters',
    'description',
    'wikipedia_url',
    'sort_order',
])]
class Promotion extends Model
{
    /** @use HasFactory<PromotionFactory> */
    use HasFactory;

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    /**
     * @param  Builder<Promotion>  $query
     * @return Builder<Promotion>
     */
    public function scopeListedOnIndex(Builder $query): Builder
    {
        return $query->whereNotNull('description');
    }

    public function activeYearsLabel(): ?string
    {
        if ($this->active_from_year === null) {
            return null;
        }

        if ($this->active_to_year === null) {
            return "{$this->active_from_year}–present";
        }

        if ($this->active_from_year === $this->active_to_year) {
            return (string) $this->active_from_year;
        }

        return "{$this->active_from_year}–{$this->active_to_year}";
    }

    public function statusLabel(): string
    {
        return $this->is_active ? 'Active' : 'Defunct';
    }

    protected function casts(): array
    {
        return [
            'founded_year' => 'integer',
            'active_from_year' => 'integer',
            'active_to_year' => 'integer',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }
}
