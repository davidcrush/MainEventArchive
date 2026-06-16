<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikipediaImportRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_wikipedia_import_does_not_attach_1996_card_to_king_of_the_ring_2000(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'King Of The Ring 2000',
            'slug' => 'king-of-the-ring-2000',
            'date' => '2000-06-25',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response([
                    'query' => [
                        'search' => [
                            ['title' => 'King of the Ring (2000)'],
                            ['title' => 'King of the Ring (1996)'],
                        ],
                    ],
                ]);
            }

            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'King of the Ring (1996)')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '1996' => [
                                'pageid' => 1996,
                                'title' => 'King of the Ring (1996)',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Stone Cold Steve Austin]] defeated [[Marc Mero]]
| stip1 = Singles match
| time1 = 16:49
}}
WIKI]]],
                                ],
                            ],
                        ],
                    ],
                ]);
            }

            if (str_contains($titles, 'King of the Ring (2000)')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '2000' => [
                                'pageid' => 2000,
                                'title' => 'King of the Ring (2000)',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => $this->kingOfTheRing2000Wikitext()]]],
                                ],
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response([
                'query' => [
                    'pages' => [
                        '404' => [
                            'title' => $titles,
                            'missing' => true,
                        ],
                    ],
                ],
            ]);
        });

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            'identifier' => 'king-of-the-ring-2000',
        ])->assertSuccessful();

        $show->refresh();

        $this->assertSame(
            'https://en.wikipedia.org/wiki/King_of_the_Ring_(2000)',
            $show->source_url,
        );
        $this->assertSame(11, WrestlingMatch::query()->where('show_id', $show->id)->count());
        $this->assertDatabaseMissing('match_participants', [
            'name' => 'Marc Mero',
        ]);
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Kurt Angle',
            'side' => 1,
        ]);
        $mainEvent = WrestlingMatch::query()
            ->where('show_id', $show->id)
            ->where('card_order', 11)
            ->first();
        $this->assertNotNull($mainEvent);
        $this->assertSame('WWF Championship', $mainEvent->title_name);
        $this->assertStringContainsString(
            'The Rock',
            $mainEvent->participants()->where('side', 1)->value('name') ?? '',
        );
    }

    /**
     * @return non-empty-string
     */
    private function kingOfTheRing2000Wikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1 = [[Rikishi (wrestler)|Rikishi]] defeated [[Chris Benoit]] by disqualification
|stip1 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time1 = 3:25
|match2 = [[Val Venis]] (with [[Trish Stratus]]) defeated [[Eddie Guerrero]] (with [[Chyna]])
|stip2 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time2 = 8:04
|match3 = [[Crash Holly]] defeated [[Bull Buchanan]]
|stip3 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time3 = 4:07
|match4 = [[Kurt Angle]] defeated [[Chris Jericho]]
|stip4 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time4 = 9:50
|match5 = [[Edge and Christian]] defeated [[Too Cool]] ([[Grand Master Sexay]] and [[Scotty 2 Hotty]]) (c), [[The Hardy Boyz]] ([[Jeff Hardy]] and [[Matt Hardy]]), and [[T & A (professional wrestling)|T & A]] ([[Matt Bloom|Albert]] and [[Test (wrestler)|Test]])
|stip5 = [[Elimination match|Fatal 4-Way elimination match]] for the [[WWF Tag Team Championship]]
|time5 = 14:11
|match6 = [[Rikishi (wrestler)|Rikishi]] defeated [[Val Venis]] (with [[Trish Stratus]])
|stip6 = [[King of the Ring tournament|King of the Ring]] semi-final match
|time6 = 3:15
|match7 = [[Kurt Angle]] defeated [[Crash Holly]]
|stip7 = [[King of the Ring tournament|King of the Ring]] semi-final match
|time7 = 3:58
|match8 = [[Pat Patterson (wrestler)|Pat Patterson]] (c) vs. [[Gerald Brisco]] ended when [[Crash Holly]] pinned Patterson
|stip8 = [[Hardcore match|Hardcore]] [[Evening gown match]] for the [[WWF Hardcore Championship]]
|time8 = 3:07
|match9 = [[D-Generation X]] ([[Tori (wrestler)|Tori]], [[Road Dogg]] and [[X-Pac]]) defeated [[The Dudley Boyz]] ([[Bubba Ray Dudley]] and [[D-Von Dudley]])
|stip9 = [[Handicap match|Handicap]] [[Professional wrestling match types#Tables match|Tables]] [[Professional wrestling match types#Dumpster match|Dumpster match]]
|time9 = 9:45
|match10 = [[Kurt Angle]] defeated [[Rikishi (wrestler)|Rikishi]]
|stip10 = [[King of the Ring tournament|King of the Ring]] final match
|time10 = 5:56
|match11 = [[Dwayne Johnson|The Rock]] and [[The Brothers of Destruction]] ([[Kane (wrestler)|Kane]] and [[The Undertaker]]) defeated The McMahon-Helmsley Faction ([[Mr. McMahon]], [[Shane McMahon]] and [[Triple H]] (c)) (with [[Stephanie McMahon-Helmsley]])
|stip11 = [[Six-man tag team match]] for the [[WWF Championship]]
|time11 = 17:54
}}
WIKI;
    }

    public function test_strict_wikipedia_import_fails_when_show_cannot_be_resolved(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'King Of The Ring 2000',
            'slug' => 'king-of-the-ring-2000',
            'date' => '2000-06-25',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response(['query' => ['search' => []]]);
            }

            return Http::response([
                'query' => [
                    'pages' => [
                        '404' => [
                            'title' => urldecode($query['titles'] ?? ''),
                            'missing' => true,
                        ],
                    ],
                ],
            ]);
        });

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            'identifier' => 'king-of-the-ring-2000',
            '--strict' => true,
        ])->assertFailed();
    }
}
