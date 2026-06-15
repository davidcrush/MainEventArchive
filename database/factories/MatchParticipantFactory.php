<?php

namespace Database\Factories;

use App\Models\MatchParticipant;
use App\Models\WrestlingMatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MatchParticipant>
 */
class MatchParticipantFactory extends Factory
{
    protected $model = MatchParticipant::class;

    public function definition(): array
    {
        return [
            'match_id' => WrestlingMatch::factory(),
            'name' => fake()->name(),
            'side' => 1,
            'is_surprise_entrant' => false,
            'placeholder_label' => null,
            'sort_order' => 0,
        ];
    }

    public function surpriseEntrant(): static
    {
        return $this->state(fn (): array => [
            'is_surprise_entrant' => true,
            'placeholder_label' => 'Mystery opponent',
        ]);
    }
}
