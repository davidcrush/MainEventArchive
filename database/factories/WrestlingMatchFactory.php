<?php

namespace Database\Factories;

use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WrestlingMatch>
 */
class WrestlingMatchFactory extends Factory
{
    protected $model = WrestlingMatch::class;

    public function definition(): array
    {
        return [
            'show_id' => Show::factory(),
            'card_order' => 1,
            'match_type' => 'singles',
            'title_name' => fake()->optional()->words(3, true),
            'is_surprise' => false,
            'is_rateable' => true,
            'is_ppv' => true,
            'winner_side' => 1,
            'finish' => 'pinfall',
            'duration_seconds' => fake()->numberBetween(300, 1800),
            'timestamp_start' => null,
            'timestamp_end' => null,
            'title_changed' => false,
        ];
    }

    public function segment(): static
    {
        return $this->state(fn (): array => [
            'match_type' => 'segment',
            'is_rateable' => false,
            'winner_side' => null,
            'finish' => null,
            'duration_seconds' => null,
        ]);
    }

    public function surprise(): static
    {
        return $this->state(fn (): array => [
            'is_surprise' => true,
        ]);
    }

    public function tournamentRound(int $round): static
    {
        return $this->state(fn (): array => [
            'tournament_round' => $round,
        ]);
    }
}
