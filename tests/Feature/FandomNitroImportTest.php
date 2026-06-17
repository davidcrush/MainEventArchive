<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FandomNitroImportTest extends TestCase
{
    use RefreshDatabase;

    private function fakeResultsPage(string $wikitext): void
    {
        Http::fake([
            'prowrestling.fandom.com/*' => function ($request) use ($wikitext) {
                parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

                if (($query['list'] ?? null) === 'search') {
                    return Http::response(['query' => ['search' => []]]);
                }

                return Http::response([
                    'query' => [
                        'pages' => [
                            '101' => [
                                'pageid' => 101,
                                'title' => urldecode($query['titles'] ?? 'Nitro'),
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => $wikitext]]],
                                ],
                            ],
                        ],
                    ],
                ]);
            },
        ]);
    }

    public function test_import_persists_spoiler_safe_cards_and_sets_fandom_source(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->nitroEpisode(37)->create([
            'promotion_id' => $promotion->id,
            'venue' => null,
            'city' => null,
        ]);

        $this->fakeResultsPage(<<<'WIKI'
{{Infobox Wrestling episode
| date = [[September 9]], [[1996]]
| venue = [[Wichita State University]]
| city = [[Wichita, Kansas]]
}}
==Results==
*[[Dark Match]]: [[The Giant]] defeated [[Johnny B. Badd]]
*[[Randy Savage]] defeated [[Ric Flair]] (c) to win the [[WCW World Heavyweight Championship]] (8:35)
WIKI);

        $this->artisan('shows:import-nitro-cards', ['--promotion' => 'wcw'])
            ->assertExitCode(0);

        $show->refresh();

        $this->assertSame('fandom', $show->source);
        $this->assertStringContainsString('prowrestling.fandom.com/wiki/', (string) $show->source_url);
        $this->assertNotNull($show->imported_at);
        $this->assertSame('Wichita State University', $show->venue);
        $this->assertSame('Wichita, Kansas', $show->city);

        $matches = $show->matches()->with('participants')->orderBy('card_order')->get();
        $this->assertCount(2, $matches);

        $titleMatch = $matches[1];
        $this->assertSame('WCW World Heavyweight Championship', $titleMatch->title_name);

        $orderedParticipants = $titleMatch->participants->sortBy('side')->pluck('name')->values()->all();
        $this->assertSame('Ric Flair', $orderedParticipants[0], 'Champion is listed first.');

        $winnerNames = $titleMatch->participants
            ->where('side', $titleMatch->winner_side)
            ->pluck('name')
            ->values()
            ->all();
        $this->assertSame(['Randy Savage'], $winnerNames);
    }

    public function test_import_preserves_existing_venue_by_default(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->nitroEpisode(37)->create([
            'promotion_id' => $promotion->id,
            'venue' => 'Existing Arena',
            'city' => 'Panama City Beach, Florida',
        ]);

        $this->fakeResultsPage(<<<'WIKI'
{{Infobox Wrestling episode
| venue = [[Lawrence Joel Veterans Memorial Coliseum]]
| city = [[Winston-Salem, North Carolina]]
}}
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
WIKI);

        $this->artisan('shows:import-nitro-cards', ['--promotion' => 'wcw'])
            ->assertExitCode(0);

        $show->refresh();

        $this->assertSame('Existing Arena', $show->venue);
        $this->assertSame('Panama City Beach, Florida', $show->city);
    }

    public function test_refresh_venues_overwrites_stale_venue_and_city(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->nitroEpisode(37)->create([
            'promotion_id' => $promotion->id,
            'venue' => 'Stale Arena',
            'city' => 'Panama City Beach, Florida',
        ]);

        $this->fakeResultsPage(<<<'WIKI'
{{Infobox Wrestling episode
| venue = [[Lawrence Joel Veterans Memorial Coliseum]]
| city = [[Winston-Salem, North Carolina]]
}}
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
WIKI);

        $this->artisan('shows:import-nitro-cards', ['--promotion' => 'wcw', '--refresh-venues' => true])
            ->assertExitCode(0);

        $show->refresh();

        $this->assertSame('Lawrence Joel Veterans Memorial Coliseum', $show->venue);
        $this->assertSame('Winston-Salem, North Carolina', $show->city);
    }

    public function test_import_skips_show_when_match_count_does_not_match(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->nitroEpisode(37)->create([
            'promotion_id' => $promotion->id,
        ]);

        $this->fakeResultsPage(<<<'WIKI'
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
*[[A Backstage Segment]] with no decision occurred
WIKI);

        $this->artisan('shows:import-nitro-cards', ['--promotion' => 'wcw'])
            ->assertExitCode(0);

        $show->refresh();

        $this->assertSame(0, $show->matches()->count(), 'Partial cards are never persisted.');
        $this->assertNotSame('fandom', $show->source);
    }

    public function test_dry_run_does_not_persist(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->nitroEpisode(37)->create([
            'promotion_id' => $promotion->id,
        ]);

        $this->fakeResultsPage(<<<'WIKI'
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
WIKI);

        $this->artisan('shows:import-nitro-cards', ['--promotion' => 'wcw', '--dry-run' => true])
            ->assertExitCode(0);

        $show->refresh();

        $this->assertSame(0, $show->matches()->count());
        $this->assertNotSame('fandom', $show->source);
    }
}
