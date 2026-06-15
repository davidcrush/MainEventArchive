<?php

namespace App\Models;

use Database\Factories\WatchedShowFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'show_id', 'watched_at'])]
class WatchedShow extends Model
{
    /** @use HasFactory<WatchedShowFactory> */
    use HasFactory;

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    protected function casts(): array
    {
        return [
            'watched_at' => 'datetime',
        ];
    }
}
