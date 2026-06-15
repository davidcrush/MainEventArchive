<?php

namespace Tests\Feature;

use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportNitroMetadataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_enriches_nitro_show_with_wikipedia_tv_rating_by_date(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WCW Monday Nitro #147',
            'slug' => 'wcw-monday-nitro-147-1998',
            'date' => '1998-08-31',
            'episode_number' => 147,
            'show_type' => ShowType::Tv,
            'venue' => 'Miami Arena',
            'city' => 'Miami, Florida',
        ]);

        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '1' => [
                            'pageid' => 1,
                            'title' => 'WCW Monday Nitro',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
==Notable episodes==
{| class="wikitable"
|-
! Episode Title !! Date !! Venue !! Location !! Rating !! Note
|-
| WCW Monday Nitro || August 31, 1998 || Miami Arena || Miami, Florida || 6.0 || Highest rated episode.
|}
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

        $this->artisan('shows:import-nitro-metadata')->assertSuccessful();

        $show = Show::query()->where('slug', 'wcw-monday-nitro-147-1998')->firstOrFail();

        $this->assertSame(6.0, (float) $show->tv_rating);
        $this->assertSame('https://en.wikipedia.org/wiki/WCW_Monday_Nitro', $show->source_url);
        $this->assertSame('Miami Arena', $show->venue);
    }
}
