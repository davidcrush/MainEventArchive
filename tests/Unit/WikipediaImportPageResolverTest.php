<?php

namespace Tests\Unit;

use App\Exceptions\WikipediaImportResolutionException;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Wikipedia\WikipediaImportPageResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikipediaImportPageResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_king_of_the_ring_2000_without_falling_back_to_1996(): void
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
                                    ['slots' => ['main' => ['*' => $this->kotr1996Wikitext()]]],
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
                                    ['slots' => ['main' => ['*' => $this->kotr2000Wikitext()]]],
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

        [$page] = app(WikipediaImportPageResolver::class)->resolve($show);

        $this->assertSame('King of the Ring (2000)', $page->canonicalTitle);
    }

    public function test_reports_all_attempts_when_no_page_can_be_parsed(): void
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

        try {
            app(WikipediaImportPageResolver::class)->resolve($show);
            $this->fail('Expected WikipediaImportResolutionException.');
        } catch (WikipediaImportResolutionException $exception) {
            $this->assertSame($show->id, $exception->show->id);
            $this->assertStringContainsString('Attempts:', $exception->getMessage());
            $this->assertNotEmpty($exception->attempts);
        }
    }

    public function test_rejects_unrelated_year_matched_search_result_for_wrestlemania(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WrestleMania X-Seven 2001',
            'slug' => 'wrestlemania-x-seven-2001',
            'date' => '2001-04-01',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response([
                    'query' => [
                        'search' => [
                            ['title' => 'WWF Backlash (2001)'],
                            ['title' => 'WrestleMania X-Seven'],
                        ],
                    ],
                ]);
            }

            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'WrestleMania X-Seven')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '2001' => [
                                'pageid' => 2001,
                                'title' => 'WrestleMania X-Seven',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => $this->wrestleManiaWikitext()]]],
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

        [$page] = app(WikipediaImportPageResolver::class)->resolve($show);

        $this->assertSame('WrestleMania X-Seven', $page->canonicalTitle);
    }

    public function test_rejects_wrong_in_your_house_number_from_search(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 22: Over The Edge 1998',
            'slug' => 'in-your-house-22-over-the-edge-1998',
            'date' => '1998-05-31',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response([
                    'query' => [
                        'search' => [
                            ['title' => 'In Your House 4'],
                            ['title' => 'Over the Edge: In Your House'],
                        ],
                    ],
                ]);
            }

            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Over the Edge: In Your House')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '1998' => [
                                'pageid' => 1998,
                                'title' => 'Over the Edge: In Your House',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => $this->inYourHouseWikitext()]]],
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

        [$page] = app(WikipediaImportPageResolver::class)->resolve($show);

        $this->assertSame('Over the Edge: In Your House', $page->canonicalTitle);
    }

    public function test_resolves_in_your_house_when_catalog_title_has_duplicate_year_suffix(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 21: Unforgiven 1998 1998',
            'slug' => 'in-your-house-21-unforgiven-1998-1998',
            'date' => '1998-04-26',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response(['query' => ['search' => []]]);
            }

            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Unforgiven: In Your House')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '1998' => [
                                'pageid' => 1998,
                                'title' => 'Unforgiven: In Your House',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => $this->inYourHouseWikitext()]]],
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

        [$page] = app(WikipediaImportPageResolver::class)->resolve($show);

        $this->assertSame('Unforgiven: In Your House', $page->canonicalTitle);
    }

    private function inYourHouseWikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Stone Cold Steve Austin]] defeated [[Dude Love]]
| stip1 = Singles match
| time1 = 16:49
}}
WIKI;
    }

    private function wrestleManiaWikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Stone Cold Steve Austin]] defeated [[The Rock]]
| stip1 = Singles match
| time1 = 16:49
}}
WIKI;
    }

    private function kotr1996Wikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Stone Cold Steve Austin]] defeated [[Marc Mero]]
| stip1 = Singles match
| time1 = 16:49
}}
WIKI;
    }

    private function kotr2000Wikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match8 = [[Pat Patterson (wrestler)|Pat Patterson]] (c) vs. [[Gerald Brisco]] ended when [[Crash Holly]] pinned Patterson
| stip8 = [[Hardcore match|Hardcore]] [[Evening gown match]] for the [[WWF Hardcore Championship]]
| time8 = 3:07
| match9 = [[Kurt Angle]] defeated [[Chris Benoit]]
| stip9 = Singles match
| time9 = 12:00
}}
WIKI;
    }
}
