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

        $this->assertContains('Bash at the Beach (1996)', $candidates);
        $this->assertContains('The Bash at the Beach (1996)', $candidates);
        $this->assertContains('Bash at the Beach 1996', $candidates);
    }

    public function test_candidates_normalize_article_casing_for_wikipedia_titles(): void
    {
        $show = new Show(['title' => 'King Of The Ring 2000']);

        $candidates = $this->resolver->candidates($show);

        $this->assertContains('King of the Ring (2000)', $candidates);
        $this->assertContains('King Of The Ring (2000)', $candidates);
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
