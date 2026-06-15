<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\MatchParticipant;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SpoilerLeakageTest extends TestCase
{
    use RefreshDatabase;

    public function test_hard_spoiler_fields_absent_when_spoilers_off(): void
    {
        $show = $this->createPublishedShow();

        $response = $this->get(route('shows.show', $show->slug));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Shows/Show')
            ->where('spoilersEnabled', false)
            ->has('show.matches.0', fn ($match) => $match
                ->missing('winner_side')
                ->missing('finish')
                ->missing('duration_seconds')
                ->etc(),
            ),
        );
    }

    public function test_hard_spoiler_fields_present_when_spoilers_on(): void
    {
        $show = $this->createPublishedShow();

        $response = $this->get(route('shows.show', ['slug' => $show->slug, 'spoilers' => 1]));

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->where('spoilersEnabled', true)
            ->where('show.matches.0.winner_side', 2)
            ->where('show.matches.0.finish', 'pinfall')
            ->where('show.matches.0.duration_seconds', 600),
        );
    }

    public function test_surprise_match_omitted_when_spoilers_off(): void
    {
        $show = $this->createPublishedShow();
        WrestlingMatch::factory()->surprise()->create([
            'show_id' => $show->id,
            'card_order' => 2,
        ]);

        $response = $this->get(route('shows.show', $show->slug));

        $response->assertInertia(fn ($page) => $page
            ->has('show.matches', 1),
        );
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

        return $show;
    }
}
