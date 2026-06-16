<?php

namespace Tests\Unit\Services\Wikipedia;

use App\Models\Show;
use App\Services\Wikipedia\WikipediaPageTitleResolver;
use Tests\TestCase;

class WikipediaPageTitleResolverTest extends TestCase
{
    public function test_in_your_house_title_uses_number_and_subtitle_without_year(): void
    {
        $show = new Show([
            'title' => 'In Your House 12: It\'s Time 1996',
        ]);

        $candidates = (new WikipediaPageTitleResolver)->candidates($show);

        $this->assertContains('In Your House 12', $candidates);
        $this->assertContains('In Your House 12: It\'s Time', $candidates);
        $this->assertNotContains('In Your House 12: It\'s Time (1996)', $candidates);
    }

    public function test_wrestlemania_title_uses_edition_without_calendar_year(): void
    {
        $show = new Show([
            'title' => 'WrestleMania XII 1996',
        ]);

        $candidates = (new WikipediaPageTitleResolver)->candidates($show);

        $this->assertContains('WrestleMania XII', $candidates);
        $this->assertNotContains('WrestleMania XII (1996)', $candidates);
    }

    public function test_wrestlemania_2000_does_not_generate_year_suffix_candidate(): void
    {
        $show = new Show([
            'title' => 'WrestleMania 2000',
        ]);

        $candidates = (new WikipediaPageTitleResolver)->candidates($show);

        $this->assertSame(['WrestleMania 2000'], $candidates);
    }
}
