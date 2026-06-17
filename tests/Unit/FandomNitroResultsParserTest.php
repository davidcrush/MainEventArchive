<?php

namespace Tests\Unit;

use App\Data\ParsedWikipediaMatch;
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

        $this->assertFalse($matches[0]->isPpv, 'Dark match is off the public card.');

        foreach (array_slice($matches, 1) as $match) {
            $this->assertTrue($match->isPpv, 'Televised matches are on the public card.');
        }
    }

    public function test_dark_match_is_flagged_not_rateable(): void
    {
        $match = $this->parser->parse($this->sampleWikitext())[0];

        $this->assertSame(1, $match->cardOrder);
        $this->assertFalse($match->isRateable);
        $this->assertFalse($match->isPpv, 'Dark match is off the public card.');
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

    public function test_unparseable_bullets_are_ignored_without_count_mismatch(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*[[The Giant]] defeated [[Johnny B. Badd]]
*[[Some Backstage Segment]] happened with no result
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
    }

    public function test_count_declared_matches_counts_only_parseable_result_bullets(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*'''Dark Match:''' ??? vs. [[Chavo Guerrero Jr.]]
*[[The Giant]] defeated [[Johnny B. Badd]]
*'''WCW United States Heavyweight Title Match:''' [[Dean Malenko]] (c) vs. [[Yuji Nagata]]
WIKI;

        $this->assertSame(1, $this->parser->countDeclaredMatches($wikitext));
    }

    public function test_count_declared_matches_counts_result_bullets(): void
    {
        $this->assertSame(5, $this->parser->countDeclaredMatches($this->sampleWikitext()));
    }

    public function test_parses_multi_line_winner_format_card(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*'''Singles Match:'''
:[[Disqo]] (w/[[Mike Sanders]]) vs. [[Jason Jett]]
:*'''Winner:''' Jason Jett (4:58).
*'''[[WCW World Cruiserweight Championship]] Match:'''
:[[Shane Helms]] (c) vs. [[Billy Kidman]]
:*'''Winner:''' Shane Helms (3:41).
*'''Singles Match:'''
:[[Konnan]] vs. [[Rick Steiner]]
:*'''Winner:''' Rick Steiner via DQ (5:06).
*'''Tag Team Match:'''
:[[Chuck Palumbo]] & [[Sean O'Haire]] vs. [[Lance Storm]] & [[Mike Awesome]]
:*'''Winner:''' Lance Storm & Mike Awesome (7:54).
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(4, $matches);
        $this->assertSame(['Jason Jett'], $this->winnerNames($matches[0]));
        $this->assertSame(298, $matches[0]->durationSeconds);
        $this->assertSame('WCW World Cruiserweight Championship', $matches[1]->titleName);
        $this->assertSame('Shane Helms', $matches[1]->participants[0]['name']);
        $this->assertSame('disqualification', $matches[2]->finish);
        $this->assertSame('tag', $matches[3]->matchType);
        $this->assertSame(['Lance Storm & Mike Awesome'], $this->winnerNames($matches[3]));
    }

    public function test_parses_inline_bold_match_type_headers_and_dash_draw_results(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*'''Singles Match:''' [[Brian Adams]] vs. [[Buff Bagwell]] - Time Limit Draw (5:44)
*'''Non Title Match:''' [[Dustin Rhodes]] vs. [[Rick Steiner]] - No Contest (1:00)
*'''WCW World Heavyweight Title Match:''' [[Scott Steiner]] (w/ [[Midajah]]) (c) defeated [[Kevin Nash]] (w/ [[David Flair]]) by DQ (5:16)
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(3, $matches);
        $this->assertNull($matches[0]->winnerSide);
        $this->assertSame('time_limit_draw', $matches[0]->finish);
        $this->assertSame('no_contest', $matches[1]->finish);
        $this->assertSame('WCW World Heavyweight Title', $matches[2]->titleName);
        $this->assertSame('disqualification', $matches[2]->finish);
    }

    public function test_skips_incomplete_bullets_without_results(): void
    {
        $wikitext = <<<'WIKI'
==Results==
*'''Dark Match:''' ??? vs. [[Chavo Guerrero Jr.]]
*'''WCW United States Heavyweight Title Match:''' [[Dean Malenko]] (c) vs. [[Yuji Nagata]]
*'''Singles Match:''' [[Goldberg]] defeated [[Buff Bagwell]] by DQ (0:29)
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame(['Goldberg'], $this->winnerNames($matches[0]));
    }

    public function test_parses_fought_to_no_contest_and_champion_role_links(): void
    {
        $wikitext = <<<'WIKI'
==Results==
* [[WCW Hardcore Championship|WCW Hardcore Champion]] [[Big Vito]] defeated [[Terry Funk]] in a [[Hardcore Match]]
*[[Jeff Jarrett]] fought [[Hulk Hogan]] to a no contest
*[[Goldberg]] defeated [[Barry Windham]] (w/ [[The West Texas Rednecks]]) at around the 30-second mark with the Jackhammer
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(3, $matches);
        $this->assertSame(['Big Vito'], $this->winnerNames($matches[0]));
        $this->assertSame('no_contest', $matches[1]->finish);
        $this->assertSame(['Goldberg'], $this->winnerNames($matches[2]));
    }

    public function test_parses_singular_result_section_header(): void
    {
        $wikitext = <<<'WIKI'
==Result==
*'''Dark Match''': [[Rob Kellum]] defeated [[Chris Adams]]
*'''Singles Match:''' [[Van Hammer]] defeated [[Kenny Kaos]] (2:30)
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);
    }

    public function test_returns_empty_card_when_event_had_no_matches(): void
    {
        $wikitext = <<<'WIKI'
==Results==
;{{small|Numbers in parentheses indicate the length of the match.}}
No Matches took place at this event.
WIKI;

        $this->assertSame(0, $this->parser->countDeclaredMatches($wikitext));
        $this->assertSame([], $this->parser->parse($wikitext));
    }
}
