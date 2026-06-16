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
                                    ['slots' => ['main' => ['*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match8 = [[Pat Patterson (wrestler)|Pat Patterson]] (c) vs. [[Gerald Brisco]] ended when [[Crash Holly]] pinned Patterson
| stip8 = [[Hardcore match|Hardcore]] [[Evening gown match]] for the [[WWF Hardcore Championship]]
| time8 = 3:07
| match9 = [[Kurt Angle]] defeated [[Chris Benoit]]
| stip9 = Singles match
| time9 = 12:00
}}
WIKI]]],
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
        $this->assertSame(2, WrestlingMatch::query()->where('show_id', $show->id)->count());
        $this->assertDatabaseMissing('match_participants', [
            'name' => 'Marc Mero',
        ]);
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Kurt Angle',
            'side' => 1,
        ]);
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
