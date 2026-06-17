<?php

namespace Tests\Unit;

use App\Services\Streaming\NetflixSavedHtmlParser;
use Tests\TestCase;

class NetflixSavedHtmlParserTest extends TestCase
{
    public function test_parses_title_links_with_aria_label(): void
    {
        $entries = (new NetflixSavedHtmlParser)->parse(
            '<a aria-label="Survivor Series 2001" href="/title/80117477">Survivor Series 2001</a>',
        );

        $this->assertCount(1, $entries);
        $this->assertSame('80117477', $entries[0]->titleId);
        $this->assertSame('Survivor Series 2001', $entries[0]->title);
    }

    public function test_parses_search_page_jbv_links(): void
    {
        $entries = (new NetflixSavedHtmlParser)->parse(<<<'HTML'
<a href="https://www.netflix.com/search?q=WWE&jbv=81929048" aria-label="WWE SummerSlam">WWE SummerSlam</a>
HTML);

        $this->assertCount(1, $entries);
        $this->assertSame('81929048', $entries[0]->titleId);
        $this->assertSame('WWE SummerSlam', $entries[0]->title);
    }

    public function test_parses_search_suggestion_video_entries(): void
    {
        $entries = (new NetflixSavedHtmlParser)->parse(<<<'HTML'
<a href="https://www.netflix.com/search?q=WWE&suggestionId=Video%3A70026497"><p>WWE: SummerSlam 2001</p></a>
HTML);

        $this->assertCount(1, $entries);
        $this->assertSame('70026497', $entries[0]->titleId);
        $this->assertSame('WWE: SummerSlam 2001', $entries[0]->title);
    }

    public function test_parses_quoted_printable_mhtml_search_page(): void
    {
        $entries = (new NetflixSavedHtmlParser)->parse(<<<'HTML'
<a href=3D"https://www.netflix.com/search?q=3DWWE&amp;jbv=3D81929048" tabindex=3D"0" aria-label=3D"WWE SummerSlam" data-uia=3D"standard-card">
HTML);

        $this->assertCount(1, $entries);
        $this->assertSame('81929048', $entries[0]->titleId);
        $this->assertSame('WWE SummerSlam', $entries[0]->title);
    }

    public function test_parses_episode_modal_items_with_video_id(): void
    {
        $entries = (new NetflixSavedHtmlParser)->parse(<<<'HTML'
<div class="titleCardList--container episode-item" tabindex="0" aria-label="SummerSlam 2001" data-uia="titleCard--container" role="button">
<div data-ui-tracking-context="%7B%22video_id%22:81929076,%22unifiedEntityId%22:%22Video:81929076%22%7D">
<span class="titleCard-title_text">SummerSlam 2001</span>
</div></div>
HTML);

        $this->assertCount(1, $entries);
        $this->assertSame('81929076', $entries[0]->titleId);
        $this->assertSame('SummerSlam 2001', $entries[0]->title);
    }

    public function test_parses_saved_wwe_summerslam_mhtml_fixture_episodes(): void
    {
        $fixturePath = base_path('docs/third-party/netflix/WWE SummerSlam - Netflix.mhtml');

        if (! is_readable($fixturePath)) {
            $this->markTestSkipped('WWE SummerSlam MHTML fixture is not available.');
        }

        $entries = (new NetflixSavedHtmlParser)->parse(file_get_contents($fixturePath) ?: '');
        $byTitle = collect($entries)->keyBy(fn ($entry) => $entry->title);

        $this->assertTrue($byTitle->has('SummerSlam 2001'));
        $this->assertSame('81929076', $byTitle->get('SummerSlam 2001')->titleId);
        $this->assertTrue($byTitle->has('SummerSlam 1996'));
    }

    public function test_parses_saved_wwe_search_mhtml_fixture(): void
    {
        $fixturePath = base_path('docs/third-party/netflix/WWE-Search.mhtml');

        if (! is_readable($fixturePath)) {
            $this->markTestSkipped('WWE search MHTML fixture is not available.');
        }

        $entries = (new NetflixSavedHtmlParser)->parse(file_get_contents($fixturePath) ?: '');

        $this->assertGreaterThan(50, count($entries));

        $titles = array_column(array_map(
            fn ($entry) => $entry->title,
            $entries,
        ), null);

        $this->assertContains('WWE SummerSlam', $titles);
        $this->assertContains('WWE: SummerSlam 2001', $titles);
    }

    public function test_parses_title_links_from_saved_html(): void
    {
        $html = <<<'HTML'
<a aria-label="Survivor Series 2001" href="/title/80117477">Survivor Series 2001</a>
<a href="https://www.netflix.com/watch/81234567" aria-label="Royal Rumble 1991">Royal Rumble 1991</a>
HTML;

        $entries = (new NetflixSavedHtmlParser)->parse($html);

        $this->assertCount(2, $entries);
        $this->assertSame('80117477', $entries[0]->titleId);
        $this->assertSame('Survivor Series 2001', $entries[0]->title);
        $this->assertSame('81234567', $entries[1]->titleId);
    }
}
