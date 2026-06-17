<?php

namespace Tests\Unit;

use App\Data\YouTubePlaylistEntry;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use App\Services\CatalogTitleMatcher;
use App\Services\Wrestling\WrestleManiaEditionResolver;
use App\Services\YouTube\YouTubeShowMatcher;
use App\Services\YouTube\YouTubeTitleParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeShowMatcherTest extends TestCase
{
    use RefreshDatabase;

    private function makeMatcher(): YouTubeShowMatcher
    {
        return new YouTubeShowMatcher(
            new YouTubeTitleParser,
            new CagematchCatalogTitleNormalizer,
            new CatalogTitleMatcher,
            new WrestleManiaEditionResolver,
        );
    }

    public function test_matches_show_by_normalized_title_and_year(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Halloween Havoc 1993',
            'date' => '1993-10-24',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

        $matcher = $this->makeMatcher();

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

    public function test_matches_wwe_ppv_by_title_and_year(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Survivor Series 2001',
            'date' => '2001-11-18',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: Survivor Series 2001 | Team WWF vs. Team Alliance and MORE!',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('Survivor Series 2001', $result['links'][0]->show->title);
    }

    public function test_matches_wwe_ppv_when_youtube_title_includes_wwe_prefix(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'SummerSlam 1998',
            'date' => '1998-08-30',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: WWE SummerSlam 1998 | Undertaker vs. Kane and MORE!',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('SummerSlam 1998', $result['links'][0]->show->title);
    }

    public function test_matches_in_your_house_by_fuzzy_subtitle(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 13: Final Four 1997',
            'date' => '1997-02-16',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry('81930499', 'In Your House - Final Four'),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('In Your House 13: Final Four 1997', $result['links'][0]->show->title);
    }

    public function test_matches_in_your_house_by_number(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 6: Rage In The Cage 1996',
            'date' => '1996-02-18',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry('81930504', 'In Your House #6'),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('In Your House 6: Rage In The Cage 1996', $result['links'][0]->show->title);
    }

    public function test_prefers_single_in_your_house_match_when_rebroadcast_title_differs(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 8: Beware Of Dog 1996',
            'date' => '1996-05-26',
            'show_type' => ShowType::Ppv,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'In Your House 8: Beware Of Dog (Wiederholung) 1996',
            'date' => '1996-05-28',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry('81930506', 'In Your House - Beware of Dog'),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('In Your House 8: Beware Of Dog 1996', $result['links'][0]->show->title);
        $this->assertSame([], $result['ambiguous']);
    }

    public function test_matches_wrestlemania_by_edition_number(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WrestleMania XII 1996',
            'date' => '1996-03-31',
            'show_type' => ShowType::Ppv,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WrestleMania X-Seven 2001',
            'date' => '2001-04-01',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->match($promotion, [
            new YouTubePlaylistEntry('81929590', 'WrestleMania 12'),
            new YouTubePlaylistEntry('81929596', 'WrestleMania 17'),
        ]);

        $this->assertCount(2, $result['links']);
        $this->assertSame(
            ['WrestleMania XII 1996', 'WrestleMania X-Seven 2001'],
            collect($result['links'])->map(fn ($link) => $link->show->title)->all(),
        );
    }

    public function test_match_wwe_nxt_maps_takeover_vengeance_day_to_catalog_title_without_takeover_prefix(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT Vengeance Day 2023',
            'date' => '2023-02-04',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: NXT TakeOver: Vengeance Day 2023 | Bron Breakker vs. Gunther',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('NXT Vengeance Day 2023', $result['links'][0]->show->title);
    }

    public function test_match_wwe_nxt_ignores_non_nxt_catalog_shows(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Royal Rumble 2023',
            'date' => '2023-01-28',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: Royal Rumble 2023 | 30-Man Royal Rumble Match',
            ),
        ]);

        $this->assertCount(0, $result['links']);
        $this->assertCount(1, $result['unmatchedEntries']);
    }

    public function test_match_wwe_nxt_maps_stand_and_deliver_title(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT Stand & Deliver 2026',
            'date' => '2026-04-04',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'abc12345678',
                'FULL EVENT: NXT Stand & Deliver 2026 | Title matches on the line',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('NXT Stand & Deliver 2026', $result['links'][0]->show->title);
    }

    public function test_match_wwe_nxt_disambiguates_stand_and_deliver_night_one_and_two(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT TakeOver: Stand & Deliver 2021',
            'date' => '2021-04-07',
            'show_type' => ShowType::Ppv,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT TakeOver: Stand & Deliver 2021',
            'date' => '2021-04-08',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $nightOne = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'night-one',
                'FULL EVENT: NXT TakeOver: Stand & Deliver 2021 – Night 1 | Shirai vs. Gonzalez',
            ),
        ]);

        $nightTwo = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'night-two',
                'FULL EVENT: NXT TakeOver: Stand & Deliver 2021 – Night 2 | Bálor vs. Kross',
            ),
        ]);

        $this->assertSame('2021-04-07', $nightOne['links'][0]->show->date->toDateString());
        $this->assertSame('2021-04-08', $nightTwo['links'][0]->show->date->toDateString());
    }

    public function test_match_wwe_nxt_maps_great_american_bash_without_the(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT The Great American Bash 2023',
            'date' => '2023-07-30',
            'show_type' => ShowType::Ppv,
        ]);

        $matcher = $this->makeMatcher();

        $result = $matcher->matchWweNxt($promotion, [
            new YouTubePlaylistEntry(
                'gab-2023',
                'FULL EVENT: NXT Great American Bash 2023 | Hayes vs. Dragunov',
            ),
        ]);

        $this->assertCount(1, $result['links']);
        $this->assertSame('NXT The Great American Bash 2023', $result['links'][0]->show->title);
    }
}
