<?php

namespace App\Models;

use Database\Factories\VideoFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'show_id',
    'match_id',
    'provider',
    'external_id',
    'url',
    'title',
    'duration_seconds',
    'embeddable',
    'embed_disabled_reason',
    'last_verified_at',
    'is_primary',
])]
class Video extends Model
{
    /** @use HasFactory<VideoFactory> */
    use HasFactory;

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function match(): BelongsTo
    {
        return $this->belongsTo(WrestlingMatch::class, 'match_id');
    }

    protected function casts(): array
    {
        return [
            'embeddable' => 'boolean',
            'is_primary' => 'boolean',
            'last_verified_at' => 'datetime',
        ];
    }
}
