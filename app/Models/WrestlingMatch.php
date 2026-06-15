<?php

namespace App\Models;

use Database\Factories\WrestlingMatchFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

#[Fillable([
    'show_id',
    'card_order',
    'match_type',
    'title_name',
    'is_surprise',
    'is_rateable',
    'is_ppv',
    'winner_side',
    'finish',
    'duration_seconds',
    'timestamp_start',
    'timestamp_end',
    'title_changed',
])]
class WrestlingMatch extends Model
{
    /** @use HasFactory<WrestlingMatchFactory> */
    use HasFactory;

    protected $table = 'matches';

    public function show(): BelongsTo
    {
        return $this->belongsTo(Show::class);
    }

    public function participants(): HasMany
    {
        return $this->hasMany(MatchParticipant::class, 'match_id')->orderBy('side')->orderBy('sort_order');
    }

    public function videos(): HasMany
    {
        return $this->hasMany(Video::class, 'match_id');
    }

    public function ratings(): MorphMany
    {
        return $this->morphMany(Rating::class, 'rateable');
    }

    public function participantLine(): string
    {
        $this->loadMissing('participants');

        if ($this->participants->isEmpty()) {
            return '—';
        }

        $sides = [];

        foreach ($this->participants->sortBy(['side', 'sort_order']) as $participant) {
            $sides[$participant->side][] = $participant->name;
        }

        ksort($sides);

        return collect($sides)
            ->map(fn (array $names): string => implode(' & ', $names))
            ->implode(' vs ');
    }

    public function resultLine(): string
    {
        if ($this->winner_side === null) {
            return '—';
        }

        $this->loadMissing('participants');

        if ($this->participants->isEmpty()) {
            return "Side {$this->winner_side} won";
        }

        $sides = [];

        foreach ($this->participants->sortBy(['side', 'sort_order']) as $participant) {
            $sides[$participant->side][] = $participant->name;
        }

        $winnerNames = $sides[$this->winner_side] ?? null;

        if ($winnerNames === null) {
            return "Side {$this->winner_side} won";
        }

        $winner = implode(' & ', $winnerNames);
        $finish = $this->finish ? ' via '.str_replace('_', ' ', $this->finish) : '';

        $loserNames = [];
        foreach ($sides as $side => $names) {
            if ($side !== $this->winner_side) {
                $loserNames[] = implode(' & ', $names);
            }
        }

        if ($loserNames !== []) {
            return "{$winner} def. ".implode(' & ', $loserNames).$finish;
        }

        return "{$winner} won{$finish}";
    }

    public function formattedDuration(): ?string
    {
        if ($this->duration_seconds === null) {
            return null;
        }

        return sprintf('%d:%02d', intdiv($this->duration_seconds, 60), $this->duration_seconds % 60);
    }

    protected function casts(): array
    {
        return [
            'is_surprise' => 'boolean',
            'is_rateable' => 'boolean',
            'is_ppv' => 'boolean',
            'title_changed' => 'boolean',
        ];
    }
}
