<?php

namespace Tests\Unit;

use App\Data\YouTubePlaylistEntry;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use App\Services\CatalogTitleMatcher;
use App\Services\YouTube\YouTubeShowMatcher;
use App\Services\YouTube\YouTubeTitleParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeShowMatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_matches_show_by_normalized_title_and_year(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Halloween Havoc 1993',
            'date' => '1993-10-24',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'ftPK-rYz7Vc',
                'FULL EVENT: WCW Halloween Havoc 1993 | Cactus Jack and Vader Spin the Wheel, Make the Deal',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('Halloween Havoc 1993', $result['links'][0]->show->title);
        $this->assertSame([], $result['ambiguous']);
        $this->assertSame([], $result['unmatchedEntries']);
    }

    public function test_matches_great_american_bash_when_catalog_title_includes_the(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'The Great American Bash 1990',
            'date' => '1990-07-07',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                '1RPmb4fQ8xQ',
                'FULL EVENT: WCW Great American Bash 1990 | Sting vs. Ric Flair; Vader debuts',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('The Great American Bash 1990', $result['links'][0]->show->title);
    }

    public function test_reports_ambiguous_when_multiple_shows_match_without_year(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1997',
            'date' => '1997-12-28',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: WCW Starrcade | Main event highlights',
            ),
        ]);

        $this->assertSame([], $result['links']);
        $this->assertCount(1, $result['ambiguous']);
    }

    public function test_reports_unmatched_entry_when_no_show_found(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $entry = new YouTubePlaylistEntry(
            'abc12345678',
            'FULL EVENT: WCW Uncensored 2099 | Future show',
        );

        $result = $matcher->match($promotion, [$entry]);

        $this->assertSame([], $result['links']);
        $this->assertSame([$entry], $result['unmatchedEntries']);
    }

    public function test_matches_clash_tv_show_by_roman_numeral_title(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Clash of the Champions XVI',
            'date' => '1991-09-05',
            'show_type' => ShowType::Tv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: WCW Clash of the Champions XVI | Fall Brawl highlights',
            ),
        ], ShowType::Tv);

        $this->assertCount(1, $result['links']);
        $this->assertSame('Clash of the Champions XVI', $result['links'][0]->show->title);
    }

    public function test_matches_clash_tv_show_when_youtube_uses_arabic_numeral(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Clash of the Champions XII',
            'date' => '1990-09-05',
            'show_type' => ShowType::Tv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'g43ml8CM9tk',
                'FULL EVENT: Clash of The Champions 12 | Lex Luger vs. Ric Flair',
            ),
        ], ShowType::Tv);

        $this->assertCount(1, $result['links']);
        $this->assertSame('Clash of the Champions XII', $result['links'][0]->show->title);
    }

    public function test_matches_nitro_show_by_air_date_in_full_episode_title(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WCW Monday Nitro #37',
            'date' => '1996-05-27',
            'episode_number' => 37,
            'show_type' => ShowType::Tv,
        ]);

        $matcher = new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
        );

        $result = $matcher->matchNitro($promotion, [
            new YouTubePlaylistEntry(
                'fSqqpe7BzNk',
                'FULL EPISODE: Scott Hall declares war on WCW: WCW Monday Nitro, May 27, 1996',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('WCW Monday Nitro #37', $result['links'][0]->show->title);
        $this->assertSame([], $result['ambiguous']);
        $this->assertSame([], $result['unmatchedEntries']);
    }
}
