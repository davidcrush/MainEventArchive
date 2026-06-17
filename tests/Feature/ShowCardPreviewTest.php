<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Http\Resources\ShowCardResource;
use App\Models\MatchParticipant;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class ShowCardPreviewTest extends TestCase
{
    use RefreshDatabase;

    private ?Promotion $promotion = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_show_card_resource_includes_main_event_preview_only(): void
    {
        $show = $this->createPublishedShow(['title' => 'Starrcade 1997', 'slug' => 'starrcade-1997']);

        $this->createMatchWithParticipants($show, cardOrder: 1, sideOne: 'Chris Jericho', sideTwo: 'Dean Malenko');
        $this->createMatchWithParticipants($show, cardOrder: 2, sideOne: 'Sting', sideTwo: 'Hollywood Hogan');
        $this->createMatchWithParticipants(
            $show,
            cardOrder: 3,
            sideOne: 'Bret Hart',
            sideTwo: 'Bill Goldberg',
            titleName: 'WCW World Heavyweight Championship',
        );
        $this->createMatchWithParticipants($show, cardOrder: 4, sideOne: 'Randy Savage', sideTwo: 'Scott Hall', isPpv: false);

        $show->load(['promotion', 'mainEventMatch.participants']);
        $show->loadCount(['matches as card_match_count' => fn ($query) => $query->where('is_ppv', true)]);

        $payload = (new ShowCardResource($show))->resolve();

        $this->assertTrue($payload['has_card']);
        $this->assertSame('Bret Hart vs Bill Goldberg', $payload['main_event_preview']['line']);
        $this->assertSame('WCW World Heavyweight Championship', $payload['main_event_preview']['title_name']);
    }

    public function test_browse_show_cards_expose_card_availability_and_main_event_preview(): void
    {
        $showWithCard = $this->createPublishedShow(['title' => 'Starrcade 1996', 'slug' => 'starrcade-1996']);
        $showWithoutCard = $this->createPublishedShow(['title' => 'Uncensored 1996', 'slug' => 'uncensored-1996']);

        $this->createMatchWithParticipants(
            $showWithCard,
            cardOrder: 1,
            sideOne: 'Ric Flair',
            sideTwo: 'Eddie Guerrero',
        );
        $this->createMatchWithParticipants(
            $showWithCard,
            cardOrder: 2,
            sideOne: 'Sting',
            sideTwo: 'Hollywood Hogan',
            titleName: 'WCW World Heavyweight Championship',
        );

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('shows.data', fn ($shows) => collect($shows)->contains(
                    fn ($show) => $show['slug'] === $showWithCard->slug
                        && $show['has_card'] === true
                        && $show['main_event_preview']['line'] === 'Sting vs Hollywood Hogan'
                        && $show['main_event_preview']['title_name'] === 'WCW World Heavyweight Championship',
                ))
                ->where('shows.data', fn ($shows) => collect($shows)->contains(
                    fn ($show) => $show['slug'] === $showWithoutCard->slug
                        && $show['has_card'] === false
                        && ! array_key_exists('main_event_preview', $show),
                )),
            );
    }

    public function test_main_event_preview_skips_surprise_matches(): void
    {
        $show = $this->createPublishedShow(['title' => 'Halloween Havoc 1996', 'slug' => 'halloween-havoc-1996']);

        $this->createMatchWithParticipants($show, cardOrder: 1, sideOne: 'Sting', sideTwo: 'Giant');
        WrestlingMatch::factory()->for($show)->create([
            'card_order' => 2,
            'is_surprise' => true,
            'is_ppv' => true,
        ]);
        $this->createMatchWithParticipants(
            $show,
            cardOrder: 3,
            sideOne: 'Hollywood Hogan',
            sideTwo: 'Randy Savage',
        );

        $show->load(['promotion', 'mainEventMatch.participants']);
        $show->loadCount(['matches as card_match_count' => fn ($query) => $query->where('is_ppv', true)]);

        $payload = (new ShowCardResource($show))->resolve();

        $this->assertSame('Hollywood Hogan vs Randy Savage', $payload['main_event_preview']['line']);
    }

    public function test_main_event_preview_masks_surprise_entrant_names(): void
    {
        $show = $this->createPublishedShow(['title' => 'SuperBrawl 1998', 'slug' => 'superbrawl-1998']);

        $match = WrestlingMatch::factory()->for($show)->create([
            'card_order' => 1,
            'is_ppv' => true,
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => 'Sting',
            'side' => 1,
            'sort_order' => 0,
        ]);
        MatchParticipant::factory()->surpriseEntrant()->create([
            'match_id' => $match->id,
            'name' => 'Secret Wrestler',
            'side' => 2,
            'sort_order' => 0,
        ]);

        $show->load(['promotion', 'mainEventMatch.participants']);
        $show->loadCount(['matches as card_match_count' => fn ($query) => $query->where('is_ppv', true)]);

        $payload = (new ShowCardResource($show))->resolve();

        $this->assertSame('Sting vs Mystery opponent', $payload['main_event_preview']['line']);
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPublishedShow(array $attributes = []): Show
    {
        $this->promotion ??= Promotion::factory()->wcw()->create();

        return Show::factory()->create(array_merge([
            'promotion_id' => $this->promotion->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'date' => '1997-12-28',
        ], $attributes));
    }

    private function createMatchWithParticipants(
        Show $show,
        int $cardOrder,
        string $sideOne,
        string $sideTwo,
        ?string $titleName = null,
        bool $isPpv = true,
    ): WrestlingMatch {
        $match = WrestlingMatch::factory()->for($show)->create([
            'card_order' => $cardOrder,
            'title_name' => $titleName,
            'is_ppv' => $isPpv,
        ]);

        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => $sideOne,
            'side' => 1,
            'sort_order' => 0,
        ]);
        MatchParticipant::factory()->create([
            'match_id' => $match->id,
            'name' => $sideTwo,
            'side' => 2,
            'sort_order' => 0,
        ]);

        return $match;
    }
}
