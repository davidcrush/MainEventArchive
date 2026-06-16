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
    'entrant_names',
    'is_surprise',
    'tournament_round',
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

    public function spoilerSafeParticipantLine(): string
    {
        if ($this->match_type !== 'battle_royal') {
            return $this->participantLine();
        }

        /** @var list<string> $entrantNames */
        $entrantNames = $this->entrant_names ?? [];
        $resultNames = $this->participants->pluck('name')->values()->all();
        $resultKeys = collect($resultNames)
            ->map(fn (string $name): string => strtolower($name))
            ->all();

        $nonResultEntrants = array_values(array_filter(
            $entrantNames,
            fn (string $name): bool => ! in_array(strtolower($name), $resultKeys, true),
        ));

        if (count($nonResultEntrants) >= 4) {
            sort($nonResultEntrants, SORT_NATURAL | SORT_FLAG_CASE);

            return $this->formatBattleRoyalFeaturingLine(
                array_slice($nonResultEntrants, 0, 4),
                count($entrantNames) > 4,
            );
        }

        if (count($entrantNames) >= 4) {
            sort($entrantNames, SORT_NATURAL | SORT_FLAG_CASE);

            return $this->formatBattleRoyalFeaturingLine(
                array_slice($entrantNames, 0, 4),
                count($entrantNames) > 4,
            );
        }

        if ($entrantNames !== []) {
            sort($entrantNames, SORT_NATURAL | SORT_FLAG_CASE);

            return $this->formatBattleRoyalFeaturingLine($entrantNames, false);
        }

        if (count($resultNames) >= 2) {
            sort($resultNames, SORT_NATURAL | SORT_FLAG_CASE);

            return $this->formatBattleRoyalFeaturingLine($resultNames, true);
        }

        if (count($resultNames) === 1) {
            return "Battle royal featuring {$resultNames[0]} and others";
        }

        return 'Battle royal';
    }

    /**
     * @param  list<string>  $names
     */
    private function formatBattleRoyalFeaturingLine(array $names, bool $hasOthers): string
    {
        if ($names === []) {
            return 'Battle royal';
        }

        if (count($names) === 1) {
            return $hasOthers
                ? "Battle royal featuring {$names[0]} and others"
                : "Battle royal featuring {$names[0]}";
        }

        if ($hasOthers) {
            $last = array_pop($names);

            return 'Battle royal featuring '.implode(', ', $names).", {$last}, and others";
        }

        $last = array_pop($names);

        if ($names === []) {
            return "Battle royal featuring {$last}";
        }

        return 'Battle royal featuring '.implode(', ', $names)." and {$last}";
    }

    public function shouldMaskTournamentParticipants(): bool
    {
        return $this->tournament_round !== null && $this->tournament_round > 1;
    }

    public function spoilerSafeTournamentParticipantLine(): string
    {
        $this->loadMissing('participants');

        if ($this->participants->isEmpty()) {
            return '??? vs ???';
        }

        $sides = [];

        foreach ($this->participants->sortBy(['side', 'sort_order']) as $participant) {
            $sides[$participant->side][] = $participant->name;
        }

        ksort($sides);

        return collect($sides)
            ->map(fn (array $names): string => implode(' & ', array_fill(0, count($names), '???')))
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
            'entrant_names' => 'array',
            'tournament_round' => 'integer',
            'is_surprise' => 'boolean',
            'is_rateable' => 'boolean',
            'is_ppv' => 'boolean',
            'title_changed' => 'boolean',
        ];
    }
}
