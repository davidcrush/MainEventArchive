<?php

namespace Tests\Unit;

use App\Services\CatalogTitleMatcher;
use PHPUnit\Framework\TestCase;

class CatalogTitleMatcherTest extends TestCase
{
    private CatalogTitleMatcher $matcher;

    protected function setUp(): void
    {
        parent::setUp();

        $this->matcher = new CatalogTitleMatcher;
    }

    public function test_matches_when_catalog_title_includes_leading_the(): void
    {
        $this->assertTrue($this->matcher->matches(
            'The Great American Bash 1990',
            'Great American Bash 1990',
        ));
    }

    public function test_matches_when_titles_differ_only_by_case(): void
    {
        $this->assertTrue($this->matcher->matches(
            'Bash At The Beach 1995',
            'Bash at the Beach 1995',
        ));
    }

    public function test_does_not_match_different_events(): void
    {
        $this->assertFalse($this->matcher->matches(
            'Starrcade 1996',
            'Starrcade 1997',
        ));
    }
}
