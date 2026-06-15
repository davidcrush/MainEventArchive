<?php

namespace Tests\Feature;

use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class WikipediaPromotionScopedImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_bulk_wikipedia_import_requires_promotion_option(): void
    {
        Promotion::factory()->wcw()->create();
        Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => Promotion::query()->where('slug', 'wcw')->value('id'),
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
        ]);

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            '--from' => '1996',
            '--to' => '1996',
        ])->assertFailed();
    }

    public function test_bulk_wikipedia_import_only_enriches_matching_promotion(): void
    {
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();

        $wcwShow = Show::factory()->create([
            'promotion_id' => $wcw->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
        ]);

        $wweShow = Show::factory()->create([
            'promotion_id' => $wwe->id,
            'title' => 'Survivor Series 1996',
            'slug' => 'survivor-series-1996',
            'date' => '1996-11-17',
            'show_type' => ShowType::Ppv,
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);
            $titles = urldecode($query['titles'] ?? '');

            if (str_contains($titles, 'Survivor Series (1996)')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '1' => [
                                'pageid' => 1,
                                'title' => 'Survivor Series (1996)',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1=[[Shawn Michaels]] defeated [[Sycho Sid]]
|stip1=Singles match
|time1=21:00
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

            if (str_contains($titles, 'Starrcade (1996)')) {
                return Http::response([
                    'query' => [
                        'pages' => [
                            '2' => [
                                'pageid' => 2,
                                'title' => 'Starrcade (1996)',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1=[[Hollywood Hogan]] defeated [[Roddy Piper]]
|stip1=Singles match
|time1=10:00
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

            return Http::response(['query' => ['search' => []]]);
        });

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            '--from' => '1996',
            '--to' => '1996',
            '--promotion' => 'wwe',
        ])->assertSuccessful();

        $this->assertSame(1, $wweShow->matches()->count());
        $this->assertSame(0, $wcwShow->fresh()->matches()->count());
    }
}
