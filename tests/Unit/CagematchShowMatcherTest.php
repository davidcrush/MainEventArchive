<?php

namespace Tests\Unit;

use App\Data\CagematchEvent;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchShowMatcher;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CagematchShowMatcherTest extends TestCase
{
    use RefreshDatabase;

    private CagematchShowMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new CagematchShowMatcher;
    }

    public function test_matches_show_by_date_and_normalized_title(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
            'cagematch_url' => null,
        ]);

        $result = $this->matcher->match(
            $promotion,
            [new CagematchEvent(1001, 'Starrcade', Carbon::parse('1996-12-29'))],
            1993,
            1996,
        );

        $this->assertCount(1, $result['links']);
        $this->assertTrue($result['links'][0]->show->is($show));
        $this->assertSame('https://www.cagematch.net/?id=1&nr=1001', $result['links'][0]->url);
        $this->assertSame([], $result['ambiguous']);
    }

    public function test_skips_shows_that_already_have_cagematch_url(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
            'cagematch_url' => 'https://www.cagematch.net/?id=1&nr=999',
        ]);

        $result = $this->matcher->match(
            $promotion,
            [new CagematchEvent(1001, 'Starrcade', Carbon::parse('1996-12-29'))],
            1993,
            1996,
        );

        $this->assertSame([], $result['links']);
    }

    public function test_reports_ambiguous_matches(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996 A',
            'slug' => 'starrcade-1996-a',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
        ]);
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996 B',
            'slug' => 'starrcade-1996-b',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
        ]);

        $result = $this->matcher->match(
            $promotion,
            [new CagematchEvent(1001, 'Starrcade', Carbon::parse('1996-12-29'))],
            1993,
            1996,
        );

        $this->assertSame([], $result['links']);
        $this->assertCount(1, $result['ambiguous']);
    }

    public function test_does_not_match_when_date_differs(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'date' => '1996-12-28',
            'show_type' => ShowType::Ppv,
        ]);

        $result = $this->matcher->match(
            $promotion,
            [new CagematchEvent(1001, 'Starrcade', Carbon::parse('1996-12-29'))],
            1993,
            1996,
        );

        $this->assertSame([], $result['links']);
        $this->assertCount(1, $result['unmatchedEvents']);
        $this->assertCount(1, $result['unmatchedShows']);
    }
}
