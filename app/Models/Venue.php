<?php

namespace App\Models;

use Database\Factories\VenueFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'slug',
    'city',
    'state_province',
    'country',
    'capacity',
    'wikipedia_page_title',
    'wikipedia_url',
    'imported_at',
])]
class Venue extends Model
{
    /** @use HasFactory<VenueFactory> */
    use HasFactory;

    public function shows(): HasMany
    {
        return $this->hasMany(Show::class);
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(VenueAlias::class);
    }

    protected function casts(): array
    {
        return [
            'imported_at' => 'datetime',
        ];
    }
}
