<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use App\Models\Venue;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikipediaImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_wikipedia_import_creates_matches_for_show(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);
            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Nashville Municipal Auditorium')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '999' => [
                                'pageid' => 999,
                                'title' => 'Nashville Municipal Auditorium',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
{{Infobox venue
| name = Nashville Municipal Auditorium
| city = Nashville
| state = Tennessee
| country = United States
| capacity = 9,700
}}
WIKI,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);
            }

            return Http::response([
                'query' => [
                    'pages' => [
                        '17823512' => [
                            'pageid' => 17823512,
                            'title' => 'Starrcade (1996)',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
{{Infobox wrestling event
|name       = Starrcade
|venue      = [[Nashville Municipal Auditorium]]
|city       = [[Nashville, Tennessee]]
|attendance = 9,030
}}
==Results==
{{Pro Wrestling results table
| match1 = [[Roddy Piper]] defeated [[Hulk Hogan|Hollywood Hogan]] by [[technical submission]]
| stip1 = Singles match
| time1 = 15:27
}}
WIKI,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            'identifier' => 'starrcade-1996',
        ])->assertSuccessful();

        $this->assertSame(1, WrestlingMatch::query()->where('show_id', $show->id)->count());
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Roddy Piper',
            'side' => 1,
        ]);
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Hollywood Hogan',
            'side' => 2,
        ]);

        $show->refresh();
        $this->assertSame('https://en.wikipedia.org/wiki/Starrcade_(1996)', $show->source_url);
        $this->assertSame('Nashville Municipal Auditorium', $show->venue);
        $this->assertSame('Nashville, Tennessee', $show->city);
        $this->assertSame(9030, $show->attendance);
        $this->assertNotNull($show->venue_id);
        $this->assertDatabaseHas('venues', [
            'name' => 'Nashville Municipal Auditorium',
            'wikipedia_page_title' => 'Nashville Municipal Auditorium',
            'capacity' => 9700,
        ]);
    }

    public function test_wikipedia_import_skips_venue_link_for_multi_venue_show(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WrestleMania 2',
            'slug' => 'wrestlemania-2',
            'date' => '1986-04-07',
        ]);

        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '1' => [
                            'pageid' => 1,
                            'title' => 'WrestleMania 2',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
{{Infobox wrestling event
| venue =
*[[Nassau Veterans Memorial Coliseum]]
*[[Allstate Arena|Rosemont Horizon]]
*[[Los Angeles Memorial Sports Arena]]
| city =
*[[Uniondale, New York]]
*[[Rosemont, Illinois]]
*[[Los Angeles, California]]
| attendance = 40,085 (combined)
}}
==Results==
{{Pro Wrestling results table
| match1 = [[Hulk Hogan]] defeated [[King Kong Bundy]]
| stip1 = Singles match
| time1 = 10:00
}}
WIKI,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            'identifier' => 'wrestlemania-2',
        ])->assertSuccessful();

        $show->refresh();
        $this->assertNull($show->venue_id);
        $this->assertSame(0, Venue::query()->count());
    }

    public function test_wikipedia_import_follows_redirect_page_titles(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Great American Bash 1995',
            'slug' => 'great-american-bash-1995',
            'date' => '1995-06-18',
        ]);

        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '52804542' => [
                            'pageid' => 52804542,
                            'title' => 'The Great American Bash (1995)',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match11=[[Ric Flair]] defeated [[Randy Savage]] (with [[Angelo Poffo]])
|stip11=Singles match
|time11=14:42
}}
WIKI,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            'identifier' => 'great-american-bash-1995',
        ])->assertSuccessful();

        $this->assertSame(1, WrestlingMatch::query()->where('show_id', $show->id)->count());
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Ric Flair',
            'side' => 1,
        ]);
        $this->assertDatabaseHas('match_participants', [
            'name' => 'Randy Savage',
            'side' => 2,
        ]);

        $show->refresh();
        $this->assertSame(
            'https://en.wikipedia.org/wiki/Great_American_Bash_(1995)',
            $show->source_url,
        );
    }
}
