<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportVenuesCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_venues_command_links_single_venue_show(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Bash at the Beach 1996',
            'slug' => 'bash-at-the-beach-1996',
            'date' => '1996-07-07',
            'venue' => 'Ocean Center',
            'source' => 'wikipedia',
            'source_url' => 'https://en.wikipedia.org/wiki/Bash_at_the_Beach_(1996)',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);
            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Ocean Center')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '555' => [
                                'pageid' => 555,
                                'title' => 'Ocean Center',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
{{Infobox convention center
| name = Ocean Center
| city = Daytona Beach
| state = Florida
| country = United States
| capacity = 8,500
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
                        '444' => [
                            'pageid' => 444,
                            'title' => 'Bash at the Beach (1996)',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
{{Infobox wrestling event
|venue = [[Ocean Center]]
|city = [[Daytona Beach, Florida]]
}}
==Results==
{{Pro Wrestling results table
| match1 = [[Booker T]] defeated [[Marc Mero]]
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
            ]);
        });

        $this->artisan('shows:import-venues', [
            '--slug' => 'bash-at-the-beach-1996',
        ])->assertSuccessful();

        $show->refresh();

        $this->assertNotNull($show->venue_id);
        $this->assertDatabaseHas('venues', [
            'id' => $show->venue_id,
            'name' => 'Ocean Center',
            'city' => 'Daytona Beach',
            'state_province' => 'Florida',
            'capacity' => 8500,
        ]);
    }

    public function test_import_venues_command_creates_redirect_alias(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Slamboree 1998',
            'slug' => 'slamboree-1998',
            'date' => '1998-05-17',
            'venue' => 'Rosemont Horizon',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);
            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Allstate Arena') || str_contains($titles, 'Rosemont Horizon')) {
                return Http::response([
                    'query' => [
                        'redirects' => [
                            ['from' => 'Rosemont Horizon', 'to' => 'Allstate Arena'],
                        ],
                        'pages' => [
                            '777' => [
                                'pageid' => 777,
                                'title' => 'Allstate Arena',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
{{Infobox arena
| stadium_name = Allstate Arena
| city = Rosemont
| state = Illinois
| country = United States
| capacity = 18,500
| former names = Rosemont Horizon
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
                        '666' => [
                            'pageid' => 666,
                            'title' => 'Slamboree (1998)',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
{{Infobox wrestling event
|venue = [[Allstate Arena|Rosemont Horizon]]
}}
==Results==
{{Pro Wrestling results table
| match1 = [[Goldberg]] defeated [[Konnan]]
| stip1 = Singles match
| time1 = 8:00
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

        $this->artisan('shows:import-venues', [
            '--slug' => 'slamboree-1998',
        ])->assertSuccessful();

        $venue = Venue::query()->where('wikipedia_page_title', 'Allstate Arena')->first();

        $this->assertNotNull($venue);
        $this->assertDatabaseHas('venue_aliases', [
            'venue_id' => $venue->id,
            'name' => 'Rosemont Horizon',
        ]);
    }
}
