<?php

namespace Tests\Unit;

use App\Data\ParsedWikipediaMatch;
use App\Exceptions\WikipediaMatchCountMismatchException;
use App\Services\Fandom\FandomNitroResultsParser;
use Tests\TestCase;

class FandomNitroResultsParserTest extends TestCase
{
    private FandomNitroResultsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(FandomNitroResultsParser::class);
    }

    private function sampleWikitext(): string
    {
        return <<<'WIKI'
{{Infobox}}
==Results==
*[[Dark Match]]: [[The Giant]] defeated [[Johnny B. Badd]]
*[[Randy Savage]] (w/[[Woman]]) defeated [[Ric Flair]] (c) (w/[[Jimmy Hart]]) to win the [[WCW World Heavyweight Championship]] (8:35)
*[[Sting]] & [[Lex Luger]] defeated [[Harlem Heat]] ([[Booker T]] & [[Stevie Ray]]) (c) to win the [[WCW World Tag Team Championship]] (9:33)
*[[Eddie Guerrero]] defeated [[Chris Benoit]] by DQ
*[[Lord Steven Regal]] (c) vs. [[Dean Malenko]] ended in a Time Limit Draw in a [[WCW World Television Championship]] Match (10:00)

==External links==
*Something
WIKI;
    }

    /**
     * @return list<string>
     */
    private function winnerNames(ParsedWikipediaMatch $match): array
    {
        return collect($match->participants)
            ->where('side', $match->winnerSide)
            ->pluck('name')
            ->values()
            ->all();
    }

    /**
     * @return list<string>
     */
    private function participantNames(ParsedWikipediaMatch $match): array
    {
        return collect($match->participants)->pluck('name')->values()->all();
    }

    public function test_parses_full_nitro_card(): void
    {
        $matches = $this->parser->parse($this->sampleWikitext());

        $this->assertCount(5, $matches);

        foreach ($matches as $match) {
            $this->assertFalse($match->isPpv, 'Nitro matches are never PPV.');
        }
    }

    public function test_dark_match_is_flagged_not_rateable(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[0];

        $this->assertSame(1, $match->cardOrder);
        $this->assertFalse($match->isRateable);
        $this->assertNull($match->titleName);
        $this->assertNull($match->durationSeconds);
        $this->assertSame(['The Giant'], $this->winnerNames($match));
    }

    public function test_championship_match_lists_champion_first_even_when_champion_loses(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[1];

        $this->assertSame('WCW World Heavyweight Championship', $match->titleName);
        $this->assertSame('Ric Flair', $match->participants[0]['name']);
        $this->assertSame(['Randy Savage'], $this->winnerNames($match));
        $this->assertSame(515, $match->durationSeconds);
        $this->assertTrue($match->isRateable);
    }

    public function test_tag_team_championship_members_and_type(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[2];

        $this->assertSame('tag', $match->matchType);
        $this->assertSame('WCW World Tag Team Championship', $match->titleName);
        $this->assertContains('Harlem Heat (Booker T & Stevie Ray)', $this->participantNames($match));
        $this->assertSame('Harlem Heat (Booker T & Stevie Ray)', $match->participants[0]['name']);
        $this->assertSame(['Sting & Lex Luger'], $this->winnerNames($match));
        $this->assertSame(573, $match->durationSeconds);
    }

    public function test_disqualification_finish(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[3];

        $this->assertSame('disqualification', $match->finish);
        $this->assertSame(['Eddie Guerrero'], $this->winnerNames($match));
        $this->assertNull($match->titleName);
    }

    public function test_time_limit_draw_has_no_winner_and_shows_both_sides(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[4];

        $this->assertNull($match->winnerSide);
        $this->assertSame('time_limit_draw', $match->finish);
        $this->assertSame('WCW World Television Championship', $match->titleName);
        $this->assertSame('Lord Steven Regal', $match->participants[0]['name']);
        $this->assertContains('Dean Malenko', $this->participantNames($match));
        $this->assertSame(600, $match->durationSeconds);
    }

    public function test_count_mismatch_throws_when_a_bullet_cannot_be_parsed(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
*[[Some Backstage Segment]] happened with no result
WIKI;

        $this->expectException(WikipediaMatchCountMismatchException::class);

        $this->parser->parse($wikitext);
    }

    public function test_count_declared_matches_counts_result_bullets(): void
    {
        $this->assertSame(5, $this->parser->countDeclaredMatches($this->sampleWikitext()));
    }
}
