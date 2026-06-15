<?php

namespace App\Models;

use Database\Factories\MatchParticipantFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'match_id',
    'name',
    'side',
    'is_surprise_entrant',
    'placeholder_label',
    'sort_order',
])]
class MatchParticipant extends Model
{
    /** @use HasFactory<MatchParticipantFactory> */
    use HasFactory;

    public function match(): BelongsTo
    {
        return $this->belongsTo(WrestlingMatch::class, 'match_id');
    }

    protected function casts(): array
    {
        return [
            'is_surprise_entrant' => 'boolean',
        ];
    }
}
