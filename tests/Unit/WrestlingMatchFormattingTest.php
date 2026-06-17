<?php

namespace Tests\Unit;

use App\Models\MatchParticipant;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WrestlingMatchFormattingTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_spoiler_safe_participant_line_features_non_finalists_when_entrant_list_exists(): void
    {
        $match = WrestlingMatch::factory()->create([
            'match_type' => 'battle_royal',
            'entrant_names' => [
                'Bradshaw',
                'Faarooq',
                'Lance Storm',
                'Billy Kidman',
                'Diamond Dallas Page',
                'Albert',
                'Test',
                'Billy Gunn',
            ],
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Test',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Billy Gunn',
            'side' => 2,
        ]);

        $line = $match->fresh()->spoilerSafeParticipantLine();

        $this->assertStringStartsWith('Battle royal featuring ', $line);
        $this->assertStringContainsString('and others', $line);
        $this->assertStringNotContainsString(' vs ', $line);
        $this->assertStringNotContainsString('Test', $line);
        $this->assertStringNotContainsString('Billy Gunn', $line);
        $this->assertStringContainsString('Albert', $line);
        $this->assertStringContainsString('Bradshaw', $line);
    }

    public function test_spoiler_safe_participant_line_falls_back_to_featuring_when_only_finalists_known(): void
    {
        $match = WrestlingMatch::factory()->create([
            'match_type' => 'battle_royal',
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Test',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Billy Gunn',
            'side' => 2,
        ]);

        $this->assertSame(
            'Battle royal featuring Billy Gunn, Test, and others',
            $match->fresh()->spoilerSafeParticipantLine(),
        );
    }

    public function test_spoiler_safe_tournament_participant_line_masks_singles(): void
    {
        $match = WrestlingMatch::factory()->tournamentRound(2)->create();

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Chris Jericho',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Stone Cold',
            'side' => 2,
        ]);

        $this->assertTrue($match->fresh()->shouldMaskTournamentParticipants());
        $this->assertSame('??? vs ???', $match->fresh()->spoilerSafeTournamentParticipantLine());
    }

    public function test_spoiler_safe_tournament_participant_line_preserves_tag_structure(): void
    {
        $match = WrestlingMatch::factory()->tournamentRound(3)->create();

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Edge',
            'side' => 1,
            'sort_order' => 0,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Christian',
            'side' => 1,
            'sort_order' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Hardy Boyz',
            'side' => 2,
            'sort_order' => 0,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Lita',
            'side' => 2,
            'sort_order' => 1,
        ]);

        $this->assertSame('??? & ??? vs ??? & ???', $match->fresh()->spoilerSafeTournamentParticipantLine());
    }

    public function test_spoiler_safe_tournament_participant_line_preserves_multi_side_structure(): void
    {
        $match = WrestlingMatch::factory()->tournamentRound(2)->create([
            'match_type' => 'triple_threat',
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Kurt Angle',
            'side' => 1,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Chris Benoit',
            'side' => 2,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Chris Jericho',
            'side' => 3,
        ]);

        $this->assertSame('??? vs ??? vs ???', $match->fresh()->spoilerSafeTournamentParticipantLine());
    }

    public function test_should_not_mask_tournament_participants_for_round_one(): void
    {
        $match = WrestlingMatch::factory()->tournamentRound(1)->create();

        $this->assertFalse($match->fresh()->shouldMaskTournamentParticipants());
    }
}
