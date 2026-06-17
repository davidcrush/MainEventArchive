<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class FandomNitroCatalogImportTest extends TestCase
{
    use RefreshDatabase;

    private function fakeNavbox(): void
    {
        $navbox = <<<'WIKI'
{| class="toccolours collapsible"
!'''[[1995 List of Monday Nitro results]]'''
|-
| [[September 4, 1995 Monday Nitro results|9/4]] • [[September 11, 1995 Monday Nitro results|9/11]]
|-
!'''[[1996 List of Monday Nitro results]]'''
|-
| [[January 1, 1996 Monday Nitro results|1/1]] • [[January 8, 1996 Monday Nitro results|1/8]]
|}
WIKI;

        Http::fake([
            'prowrestling.fandom.com/*' => Http::response([
                'query' => [
                    'pages' => [
                        '17737' => [
                            'pageid' => 17737,
                            'title' => 'Template:WCW Nitro results',
                            'revisions' => [
                                ['slots' => ['main' => ['*' => $navbox]]],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);
    }

    public function test_seeds_pending_review_shells_for_every_episode(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $this->fakeNavbox();

        $this->artisan('shows:seed-nitro-catalog', ['--promotion' => 'wcw'])
            ->assertExitCode(0);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Tv)
            ->orderBy('date')
            ->get();

        $this->assertCount(4, $shows);
        $this->assertSame('WCW Monday Nitro #1', $shows->first()->title);
        $this->assertSame(1, $shows->first()->episode_number);
        $this->assertSame('1995-09-04', $shows->first()->date->toDateString());
        $this->assertSame(ShowStatus::PendingReview, $shows->first()->status);
        $this->assertSame('fandom', $shows->first()->source);

        $this->assertSame('WCW Monday Nitro #3', $shows->get(2)->title);
        $this->assertSame('1996-01-01', $shows->get(2)->date->toDateString());
    }

    public function test_is_idempotent_and_does_not_duplicate(): void
    {
        Promotion::factory()->wcw()->create();
        $this->fakeNavbox();

        $this->artisan('shows:seed-nitro-catalog', ['--promotion' => 'wcw'])->assertExitCode(0);
        $this->artisan('shows:seed-nitro-catalog', ['--promotion' => 'wcw'])->assertExitCode(0);

        $this->assertSame(4, Show::query()->where('title', 'like', 'WCW Monday Nitro%')->count());
    }

    public function test_year_filter_limits_scope(): void
    {
        Promotion::factory()->wcw()->create();
        $this->fakeNavbox();

        $this->artisan('shows:seed-nitro-catalog', ['--promotion' => 'wcw', '--from' => '1996', '--to' => '1996'])
            ->assertExitCode(0);

        $shows = Show::query()->where('title', 'like', 'WCW Monday Nitro%')->get();

        $this->assertCount(2, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->date->year === 1996));
    }

    public function test_dry_run_does_not_write(): void
    {
        Promotion::factory()->wcw()->create();
        $this->fakeNavbox();

        $this->artisan('shows:seed-nitro-catalog', ['--promotion' => 'wcw', '--dry-run' => true])
            ->assertExitCode(0);

        $this->assertSame(0, Show::query()->where('title', 'like', 'WCW Monday Nitro%')->count());
    }
}
