<?php

namespace Tests\Unit;

use App\Models\MatchParticipant;
use App\Models\WrestlingMatch;
use Tests\TestCase;

class WrestlingMatchFormattingTest extends TestCase
{
    public function test_participant_line_formats_sides(): void
    {
        $match = WrestlingMatch::factory()->create();

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Sting',
            'side' => 1,
            'sort_order' => 0,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Hollywood Hogan',
            'side' => 2,
            'sort_order' => 0,
        ]);

        $this->assertSame('Sting vs Hollywood Hogan', $match->fresh()->participantLine());
    }

    public function test_participant_line_returns_dash_when_empty(): void
    {
        $match = WrestlingMatch::factory()->create();

        $this->assertSame('—', $match->participantLine());
    }

    public function test_result_line_formats_winner_loser_and_finish(): void
    {
        $match = WrestlingMatch::factory()->create([
            'winner_side' => 1,
            'finish' => 'submission',
            'duration_seconds' => 862,
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Sting',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Hollywood Hogan',
            'side' => 2,
        ]);

        $this->assertSame('Sting def. Hollywood Hogan via submission', $match->fresh()->resultLine());
        $this->assertSame('14:22', $match->formattedDuration());
    }

    public function test_result_line_formats_battle_royal_winner_only(): void
    {
        $match = WrestlingMatch::factory()->create([
            'match_type' => 'battle_royal',
            'winner_side' => 1,
            'finish' => 'last_elimination',
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'The Giant',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Lex Luger',
            'side' => 2,
        ]);

        $this->assertSame('The Giant def. Lex Luger via last elimination', $match->fresh()->resultLine());
    }
}
