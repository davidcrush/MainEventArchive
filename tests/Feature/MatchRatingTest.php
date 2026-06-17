<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\MatchParticipant;
use App\Models\Promotion;
use App\Models\Rating;
use App\Models\Show;
use App\Models\User;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MatchRatingTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_user_can_store_match_rating_on_server(): void
    {
        $user = User::factory()->create();
        $show = $this->createPublishedShow();
        $match = $show->matches->first();

        Rating::factory()->create([
            'user_id' => $user->id,
            'rateable_type' => WrestlingMatch::class,
            'rateable_id' => $match->id,
            'stars' => 3,
        ]);

        $this->actingAs($user)
            ->post(route('ratings.store'), [
                'rateable_type' => 'match',
                'rateable_id' => $match->id,
                'stars' => 5,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('ratings', [
            'user_id' => $user->id,
            'rateable_type' => WrestlingMatch::class,
            'rateable_id' => $match->id,
            'stars' => 5,
        ]);
    }

    private function createPublishedShow(): Show
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997',
            'date' => '1997-12-28',
        ]);

        $match = WrestlingMatch::factory()->create([
            'show_id' => $show->id,
            'card_order' => 1,
            'winner_side' => 2,
            'finish' => 'pinfall',
            'duration_seconds' => 600,
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Sting',
            'side' => 2,
        ]);

        return $show->fresh(['matches']);
    }
}
