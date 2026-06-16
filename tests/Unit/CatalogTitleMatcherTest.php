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

    public function test_fuzzy_phrase_matches_minor_differences(): void
    {
        $this->assertTrue($this->matcher->fuzzyPhraseMatches(
            "It's Time!",
            "It's Time",
        ));

        $this->assertTrue($this->matcher->fuzzyPhraseMatches(
            'A Cold Day in Hell',
            'A Cold Day In Hell',
        ));

        $this->assertTrue($this->matcher->fuzzyPhraseMatches(
            'Revenge of the Taker',
            'Revenge Of The Taker',
        ));

        $this->assertFalse($this->matcher->fuzzyPhraseMatches(
            'Final Four',
            'Mind Games',
        ));
    }

    public function test_extracts_in_your_house_catalog_parts(): void
    {
        $parts = $this->matcher->extractInYourHouseCatalogParts('In Your House 13: Final Four 1997');

        $this->assertNotNull($parts);
        $this->assertSame(13, $parts['number']);
        $this->assertSame('Final Four', $parts['subtitle']);
    }
}
