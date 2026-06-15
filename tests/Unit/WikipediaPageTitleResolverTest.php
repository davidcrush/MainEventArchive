<?php

namespace Tests\Unit;

use App\Models\Show;
use App\Services\Wikipedia\WikipediaPageTitleResolver;
use Tests\TestCase;

class WikipediaPageTitleResolverTest extends TestCase
{
    private WikipediaPageTitleResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = app(WikipediaPageTitleResolver::class);
    }

    public function test_candidates_include_fall_brawl_tagline_title(): void
    {
        $show = new Show(['title' => 'Fall Brawl 1996']);

        $candidates = $this->resolver->candidates($show);

        $this->assertContains('Fall Brawl (1996)', $candidates);
        $this->assertContains("Fall Brawl '96: War Games", $candidates);
    }

    public function test_candidates_use_standard_year_format_for_other_shows(): void
    {
        $show = new Show(['title' => 'Bash at the Beach 1996']);

        $candidates = $this->resolver->candidates($show);

        $this->assertSame([
            'Bash at the Beach (1996)',
            'The Bash at the Beach (1996)',
            'Bash at the Beach 1996',
        ], $candidates);
    }

    public function test_candidates_include_the_prefix_for_great_american_bash(): void
    {
        $show = new Show(['title' => 'Great American Bash 1995']);

        $candidates = $this->resolver->candidates($show);

        $this->assertContains('Great American Bash (1995)', $candidates);
        $this->assertContains('The Great American Bash (1995)', $candidates);
    }

    public function test_candidates_include_curated_pre_1990_wikipedia_page_title_override(): void
    {
        $show = new Show(['title' => 'Starrcade 1987']);

        $candidates = $this->resolver->candidates($show);

        $this->assertSame("Starrcade '87: Chi-Town Heat", $candidates[0]);
    }

    public function test_candidates_include_curated_clash_wikipedia_page_title_override(): void
    {
        $show = new Show(['title' => 'Clash of the Champions XVI']);

        $candidates = $this->resolver->candidates($show);

        $this->assertSame('Clash of the Champions XVI: Fall Brawl', $candidates[0]);
    }
}
