<?php

namespace Tests\Unit;

use App\Data\ParsedWikipediaMatch;
use App\Exceptions\WikipediaMatchCountMismatchException;
use App\Services\Wikipedia\WikipediaResultsParser;
use Tests\TestCase;

class WikipediaResultsParserTest extends TestCase
{
    private WikipediaResultsParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(WikipediaResultsParser::class);
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

    public function test_parses_pro_wrestling_results_template_for_starrcade_1996(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| results = <ref name=411mania/>
| match1 = [[Último Dragón|Ultimate Dragon]] (J-Crown) (with [[Sonny Onoo]]) defeated [[Dean Malenko]] (Cruiserweight)
| stip1 = [[Undisputed championship (professional wrestling)|Title Unification match]] for the [[J-Crown]] and the [[WWE Cruiserweight Championship (1991-2007)|WCW Cruiserweight Championship]]
| time1 = 18:30
| match8 = [[Roddy Piper]] defeated [[Hulk Hogan|Hollywood Hogan]] (with [[Ted DiBiase]]) by [[technical submission]]
| stip8 = Non-title Singles match
| time8 = 15:27
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);

        $this->assertSame(1, $matches[0]->cardOrder);
        $this->assertSame('Ultimate Dragon', $matches[0]->participants[0]['name']);
        $this->assertSame('Dean Malenko', $matches[0]->participants[1]['name']);
        $this->assertSame(1110, $matches[0]->durationSeconds);
        $this->assertSame(1, $matches[0]->winnerSide);
        $this->assertSame('pinfall', $matches[0]->finish);

        $this->assertSame(8, $matches[1]->cardOrder);
        $this->assertSame('Roddy Piper', $matches[1]->participants[0]['name']);
        $this->assertSame('Hollywood Hogan', $matches[1]->participants[1]['name']);
        $this->assertSame('technical submission', $matches[1]->finish);
    }

    public function test_parses_battle_royal_last_elimination_format(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match8 = [[The Outsiders]] defeated [[The Nasty Boys]]
|stip8 = Triangle match
|time8 = 16:11
|match9 = [[Big Show|The Giant]] won by last eliminating [[Lex Luger]]
|stip9 = [[Battle royal (professional wrestling)#World War 3|60-Man World War 3 match]] for a future [[WCW World Heavyweight Championship]] match{{Ref|1|1}}
|time9 = 28:21
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);

        $battleRoyal = $matches[1];
        $this->assertSame(9, $battleRoyal->cardOrder);
        $this->assertSame('battle_royal', $battleRoyal->matchType);
        $this->assertSame('The Giant', $battleRoyal->participants[0]['name']);
        $this->assertSame(1, $battleRoyal->participants[0]['side']);
        $this->assertSame('Lex Luger', $battleRoyal->participants[1]['name']);
        $this->assertSame(2, $battleRoyal->participants[1]['side']);
        $this->assertSame('last_elimination', $battleRoyal->finish);
        $this->assertSame(1701, $battleRoyal->durationSeconds);
        $this->assertSame('WCW World Heavyweight Championship', $battleRoyal->titleName);
    }

    public function test_parses_battle_royal_last_defeating_format(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match2 = [[The Rock]] won by last defeating [[Big Show]]
| stip2 = [[Battle royal]]
| time2 = 12:34
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame('The Rock', $matches[0]->participants[0]['name']);
        $this->assertSame('Big Show', $matches[0]->participants[1]['name']);
        $this->assertSame('last_elimination', $matches[0]->finish);
    }

    public function test_parses_battle_royal_won_when_eliminated_each_other_format(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match2 = [[D'Lo Brown]] and [[Test (wrestler)|Test]] won when [[Droz (wrestler)|Droz]] and [[Charles Wright (wrestler)|The Godfather]] eliminated each other<ref group="Note">The other participants were: [[Faarooq]] and [[Bradshaw]].</ref>
| stip2 = [[Battle royal (professional wrestling)|Battle Royal]]
| time2 = 12:34
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame("D'Lo Brown", $matches[0]->participants[0]['name']);
        $this->assertSame('Test', $matches[0]->participants[1]['name']);
        $this->assertSame('Droz', $matches[0]->participants[2]['name']);
        $this->assertSame('The Godfather', $matches[0]->participants[3]['name']);
        $this->assertSame('last_elimination', $matches[0]->finish);
        $this->assertSame(['Faarooq', 'Bradshaw'], $matches[0]->entrantNames);
    }

    public function test_parses_battle_royal_last_elimination_with_quoted_phrase(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1 = Randy Savage won by "last eliminating" [[Hulk Hogan]]
|stip1 = [[Battle royal (professional wrestling)#World War 3|60-Man World War 3 match]]
|time1 = 27:00
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame('Randy Savage', $matches[0]->participants[0]['name']);
        $this->assertSame('Hulk Hogan', $matches[0]->participants[1]['name']);
        $this->assertSame('last_elimination', $matches[0]->finish);
    }

    public function test_parses_bash_at_the_beach_template_with_dark_and_main_event_matches(): void
    {
        $wikitext = <<<'WIKI'
==Results==
<section begin=Results/>
{{Pro wrestling results table
|note1=dark
|match1=[[Jim Powers]] defeated [[Hugh Morrus]]
|stip1=Singles match
|time1=04:23
|note2=wcwme
|match2=[[The Steiner Brothers]] defeated [[Harlem Heat]] by disqualification
|stip2=Tag team match
|time2=05:01
|match6=[[Rey Misterio Jr.]] defeated [[Psychosis]]
|stip6=Singles match
|time6=15:18
|match14=[[The Outsiders]] and [[Hulk Hogan]] vs. [[Randy Savage]], [[Sting]] and [[Lex Luger]] ended in a [[no contest]]
|stip14=Six-man tag team match
|time14=16:55
}}
<section end=Results/>
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(4, $matches);

        $this->assertFalse($matches[0]->isPpv);
        $this->assertSame('Jim Powers', $matches[0]->participants[0]['name']);

        $this->assertFalse($matches[1]->isPpv);
        $this->assertSame('disqualification', $matches[1]->finish);

        $this->assertTrue($matches[2]->isPpv);
        $this->assertSame(6, $matches[2]->cardOrder);

        $mainEvent = $matches[3];
        $this->assertTrue($mainEvent->isPpv);
        $this->assertSame(14, $mainEvent->cardOrder);
        $this->assertNull($mainEvent->winnerSide);
        $this->assertSame('no_contest', $mainEvent->finish);
        $sideOne = collect($mainEvent->participants)->where('side', 1)->pluck('name')->all();
        $sideTwo = collect($mainEvent->participants)->where('side', 2)->pluck('name')->all();
        $this->assertStringContainsString('The Outsiders', $sideOne[0]);
        $this->assertStringContainsString('Hulk Hogan', $sideOne[0]);
        $this->assertStringContainsString('Randy Savage', $sideTwo[0]);
        $this->assertStringContainsString('Sting', $sideTwo[0]);
        $this->assertStringContainsString('Lex Luger', $sideTwo[0]);
    }

    public function test_parses_lowercase_pro_wrestling_template_name(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro wrestling results table
|match1=[[Chris Benoit]] defeated [[Chris Jericho]]
|stip1=Singles match
|time1=14:36
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertContains('Chris Benoit', $this->participantNames($matches[0]));
        $this->assertContains('Chris Jericho', $this->participantNames($matches[0]));
        $this->assertSame(['Chris Benoit'], $this->winnerNames($matches[0]));
    }

    public function test_parses_tag_teams_with_team_name_and_members(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match8=[[The Outsiders (professional wrestling)|The Outsiders]] ([[Scott Hall]] and [[Kevin Nash]]) (c) defeated [[Faces of Fear]] ([[Meng (wrestler)|Meng]] and [[The Barbarian (wrestler)|The Barbarian]]) (with [[Jimmy Hart]]) and [[The Nasty Boys]] ([[Brian Knobbs]] and [[Jerry Sags]])
|stip8=[[Professional wrestling match types#Basic non-elimination matches|Triangle match]] for the [[WCW World Tag Team Championship]]
|time8=16:11
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame('triple_threat', $matches[0]->matchType);

        $participants = collect($matches[0]->participants)->keyBy('side');

        $this->assertSame('The Outsiders (Scott Hall & Kevin Nash)', $participants[1]['name']);
        $this->assertSame('Faces of Fear (Meng & The Barbarian)', $participants[2]['name']);
        $this->assertSame('The Nasty Boys (Brian Knobbs & Jerry Sags)', $participants[3]['name']);
    }

    public function test_parses_great_american_bash_1995_template(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|note1=wcwme
|match1=[[Harlem Heat]] ([[Booker T (wrestler)|Booker T]] and [[Stevie Ray]]) (with [[Sherri Martel|Sister Sherri]]) defeated [[The Fantastics]] ([[Bobby Fulton]] and [[Tommy Rogers (wrestler)|Tommy Rogers]])
|stip1=[[Professional wrestling tag team match types|Tag team match]]
|time1=06:46
|match3=[[Dick Slater]] and [[Bunkhouse Buck]] (with [[Robert Fuller (wrestler)|Col. Robert Parker]]) defeated [[Frankie Lancaster]] and [[Barry Houston]]
|stip3=Tag team match
|time3=03:52
|match6=[[Jim Duggan]] defeated Sgt. Craig Pittman by [[Professional wrestling#Disqualification|disqualification]]
|stip6=Singles match
|time6=08:13
|match10=[[Sting (wrestler)|Sting]] defeated Meng (with Col. Robert Parker)
|stip10=Singles match for the vacant [[WWE United States Championship|WCW United States Heavyweight Championship]]
|time10=13:34
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(4, $matches);

        $this->assertFalse($matches[0]->isPpv);
        $this->assertSame(['Harlem Heat (Booker T & Stevie Ray)'], $this->winnerNames($matches[0]));

        $this->assertSame(['Dick Slater & Bunkhouse Buck'], $this->winnerNames($matches[1]));
        $this->assertContains('Frankie Lancaster & Barry Houston', $this->participantNames($matches[1]));

        $this->assertSame('disqualification', $matches[2]->finish);
        $this->assertSame(['Jim Duggan'], $this->winnerNames($matches[2]));

        $this->assertSame(['Sting'], $this->winnerNames($matches[3]));
        $this->assertContains('Meng', $this->participantNames($matches[3]));
        $this->assertSame('WCW United States Heavyweight Championship', $matches[3]->titleName);
    }

    public function test_parses_six_man_tag_with_comma_separated_winners_and_mixed_losers(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match2 = [[Randy Savage]], [[Scott Norton]] and [[Virgil (wrestler)|Vincent]] (with [[Miss Elizabeth]]) defeated [[Big Boss Man (wrestler)|Ray Traylor]] and [[The Steiner Brothers]] ([[Rick Steiner]] and [[Scott Steiner]]) (with [[Ted DiBiase]])
|stip2 = [[Professional wrestling tag team match types#Multiple man teamed matches|Six-man tag team match]]
|time2 = 11:06
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame('tag', $matches[0]->matchType);
        $this->assertSame(666, $matches[0]->durationSeconds);

        $participants = collect($matches[0]->participants)->keyBy('side');

        $this->assertSame(
            'Randy Savage & Scott Norton & Vincent',
            $participants[1]['name'],
        );
        $this->assertSame(
            'Ray Traylor & The Steiner Brothers (Rick Steiner & Scott Steiner)',
            $participants[2]['name'],
        );
        $this->assertSame(1, $matches[0]->winnerSide);
    }

    public function test_parses_in_your_house_template_with_free_for_all_and_post_show_dark_matches(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|note1=ffa
|match1=[[Rocky Maivia]] defeated [[Salvatore Sincere]] by disqualification
|stip1=Singles match
|time1=6:01
|match2=[[Flash Funk]] defeated [[Leif Cassidy]]
|stip2=Singles match
|time2=10:34
|note7=dark
|match7=[[Brakkus]] defeated [[Dr. X]]
|stip7=Singles match
|time7=5:25
|note8=dark
|match8=[[Stone Cold Steve Austin]] defeated [[Goldust]]
|stip8=Singles match
|time8=12:18
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(4, $matches);

        $this->assertFalse($matches[0]->isPpv);
        $this->assertSame(1, $matches[0]->cardOrder);
        $this->assertSame('Rocky Maivia', $matches[0]->participants[0]['name']);

        $this->assertTrue($matches[1]->isPpv);
        $this->assertSame(2, $matches[1]->cardOrder);

        $this->assertFalse($matches[2]->isPpv);
        $this->assertSame(7, $matches[2]->cardOrder);

        $this->assertFalse($matches[3]->isPpv);
        $this->assertSame(8, $matches[3]->cardOrder);
    }

    public function test_parses_wikitable_free_for_all_suffix_as_pre_show(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{| class="wikitable"
|-
! No.
! Results
! Stipulations
! Times
|-
|1F
|[[Rocky Maivia]] defeated [[Salvatore Sincere]] by disqualification
|Singles match
|6:01
|-
|1
|[[Flash Funk]] defeated [[Leif Cassidy]]
|Singles match
|10:34
|}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);
        $this->assertFalse($matches[0]->isPpv);
        $this->assertTrue($matches[1]->isPpv);
    }

    public function test_parses_wwe_heat_template_note_as_pre_show(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|note1=heat
|match1=[[Trish Stratus]], [[Jacqueline]] and [[Molly Holly]] defeated [[Ivory (wrestler)|Ivory]], [[Lita (wrestler)|Lita]] and [[Tori (wrestler)|Tori]]
|stip1=[[Professional wrestling tag team match types#Multiple man teamed matches|Six-woman tag team match]]
|time1=04:12
|match2=[[The Rock]] defeated [[Booker T (wrestler)|Booker T]]
|stip2=Singles match
|time2=12:30
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);
        $this->assertFalse($matches[0]->isPpv);
        $this->assertSame(1, $matches[0]->cardOrder);
        $this->assertTrue($matches[1]->isPpv);
    }

    public function test_parses_pro_wrestling_results_table_with_nested_cite_refs_and_inline_closing_braces(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|results = <ref name=pwh>{{cite web|url=https://example.com|title=SummerSlam 2005}}</ref>
|times = <ref name=pwh/>
|note1 = heat
|match1 = [[Chris Masters]] defeated [[Gregory Helms|The Hurricane]]
|stip1 = Singles match
|time1 = 1:56
|match2 = [[Edge (wrestler)|Edge]] defeated [[Matt Hardy]] by Technical Knockout
|stip2 = Singles match
|time2 = 4:50}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);
        $this->assertFalse($matches[0]->isPpv);
        $this->assertContains('Chris Masters', array_column($matches[0]->participants, 'name'));
        $this->assertContains('The Hurricane', array_column($matches[0]->participants, 'name'));
        $this->assertTrue($matches[1]->isPpv);
        $this->assertSame('Technical Knockout', $matches[1]->finish);
        $this->assertContains('Edge', array_column($matches[1]->participants, 'name'));
        $this->assertContains('Matt Hardy', array_column($matches[1]->participants, 'name'));
    }

    public function test_parses_wikitable_heat_suffix_as_pre_show(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{| class="wikitable"
|-
! No.
! Results
! Stipulations
! Times
|-
|1H
|[[Trish Stratus]], [[Jacqueline]] and [[Molly Holly]] defeated [[Ivory (wrestler)|Ivory]], [[Lita (wrestler)|Lita]] and [[Tori (wrestler)|Tori]]
|Six-woman tag team match
|4:12
|-
|1
|[[The Rock]] defeated [[Booker T (wrestler)|Booker T]]
|Singles match
|12:30
|}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(2, $matches);
        $this->assertFalse($matches[0]->isPpv);
        $this->assertSame(1, $matches[0]->cardOrder);
        $this->assertTrue($matches[1]->isPpv);
    }

    public function test_ignores_battle_royal_entrant_footnotes_when_parsing_last_elimination(): void
    {
        $wikitext = <<<'WIKI'
== Results ==
{{Pro Wrestling results table
|match6 = [[Test (wrestler)|Test]] (Alliance) won by last eliminating [[Billy Gunn]] (WWF)<ref group=Note>The other participants were [[John Layfield|Bradshaw]] (WWF), [[Faarooq]] (WWF), [[Lance Storm]] (Alliance), [[Billy Kidman]] (Alliance), [[Diamond Dallas Page]] (Alliance), [[Matt Bloom|Albert]] (WWF), [[Tazz]], [[Perry Saturn]] (WWF), [[Scott Levy|Raven]] (Alliance), [[Chuck Palumbo]] (WWF), [[Crash Holly]] (WWF), [[Justin Credible]] (Alliance), [[Shawn Stasiak]] (Alliance), [[Stevie Richards|Steven Richards]] (Alliance), [[Tommy Dreamer]] (Alliance), [[Gregory Helms|The Hurricane]] (Alliance), [[Spike Dudley]] (WWF), [[Hugh Morrus]], [[Chavo Guerrero]], and [[Shoichi Funaki|Funaki]] (WWF). Tazz, Morrus, and Guerrero entered the match after it had begun, although they all departed from the Alliance at different points in the week prior to Survivor Series; thus, they were referred to as "wild cards" by the commentators at the event.</ref>
|stip6 = Immunity Battle Royal
|time6 = 10:00
}}
WIKI;

        $matches = $this->parser->parse($wikitext, 'Survivor Series (2001)', 'Survivor Series 2001');

        $this->assertCount(1, $matches);
        $this->assertSame('battle_royal', $matches[0]->matchType);
        $this->assertSame('Test', $matches[0]->participants[0]['name']);
        $this->assertSame('Billy Gunn', $matches[0]->participants[1]['name']);
        $this->assertLessThan(255, strlen($matches[0]->participants[1]['name']));
        $this->assertGreaterThanOrEqual(4, count($matches[0]->entrantNames));
        $this->assertContains('Bradshaw', $matches[0]->entrantNames);
        $this->assertNotContains('Test', $matches[0]->entrantNames);
    }

    public function test_scopes_results_to_event_subsection_on_multi_event_clash_pages(): void
    {
        $wikitext = <<<'WIKI'
==Results==
===National Wrestling Alliance (Jim Crockett Promotions)===
====Clash of the Champions I====
{{Pro Wrestling results table
|match1=[[Mike Rotunda]] defeated [[Jimmy Garvin]]
|stip1=Singles match
|time1=10:00
}}
====Clash of the Champions V: St. Valentine's Massacre====
{{Pro Wrestling results table
|match1=[[The Midnight Express (professional wrestling)|The Midnight Express]] ([[Bobby Eaton]] and [[Stan Lane]]) defeated The Russian Assassins
|stip1=Tag team match
|time1=12:34
}}
WIKI;

        $matches = $this->parser->parse(
            $wikitext,
            'Clash of the Champions V: St. Valentine\'s Massacre',
            'Clash of the Champions V',
        );

        $this->assertCount(1, $matches);
        $this->assertSame('The Midnight Express (Bobby Eaton & Stan Lane)', $matches[0]->participants[0]['name']);
        $this->assertSame('The Russian Assassins', $matches[0]->participants[1]['name']);
    }

    public function test_parses_ended_when_result_as_no_contest_style_match(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match8 = [[Pat Patterson (wrestler)|Pat Patterson]] (c) vs. [[Gerald Brisco]] ended when [[Crash Holly]] pinned Patterson
| stip8 = [[Hardcore match|Hardcore]] [[Evening gown match]] for the [[WWF Hardcore Championship]]
| time8 = 3:07
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertCount(1, $matches);
        $this->assertSame(8, $matches[0]->cardOrder);
        $this->assertNull($matches[0]->winnerSide);
        $this->assertSame('Pat Patterson', $matches[0]->participants[0]['name']);
        $this->assertSame('Gerald Brisco', $matches[0]->participants[1]['name']);
        $this->assertStringContainsString('Crash Holly pinned Patterson', $matches[0]->finish);
    }

    public function test_does_not_split_and_inside_wiki_link_team_names(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match5 = [[Edge and Christian]] defeated [[Too Cool]] ([[Grand Master Sexay]] and [[Scotty 2 Hotty]]) (c), [[The Hardy Boyz]] ([[Jeff Hardy]] and [[Matt Hardy]]), and [[T & A (professional wrestling)|T & A]] ([[Matt Bloom|Albert]] and [[Test (wrestler)|Test]])
|stip5 = [[Elimination match|Fatal 4-Way elimination match]] for the [[WWF Tag Team Championship]]
|time5 = 14:11
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertContains('Edge and Christian', $this->participantNames($matches[0]));
        $this->assertSame('WWF Tag Team Championship', $matches[0]->titleName);
        $this->assertStringContainsString('Too Cool', $matches[0]->participants[0]['name']);
        $this->assertSame(['Edge and Christian'], $this->winnerNames($matches[0]));
    }

    public function test_championship_match_lists_champion_first_even_when_champion_loses(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Bret Hart]] defeated [[Ric Flair]] (c)
| stip1 = Singles match for the [[WCW World Heavyweight Championship]]
| time1 = 20:00
}}
WIKI;

        $matches = $this->parser->parse($wikitext);

        $this->assertSame('WCW World Heavyweight Championship', $matches[0]->titleName);
        $this->assertSame('Ric Flair', $matches[0]->participants[0]['name']);
        $this->assertSame(['Bret Hart'], $this->winnerNames($matches[0]));
        $this->assertSame(2, $matches[0]->winnerSide);
    }

    public function test_non_title_match_order_is_independent_of_winner(): void
    {
        $alphaWins = $this->parser->parse(<<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Alpha]] defeated [[Bravo]]
| stip1 = Singles match
| time1 = 5:00
}}
WIKI);

        $bravoWins = $this->parser->parse(<<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Bravo]] defeated [[Alpha]]
| stip1 = Singles match
| time1 = 5:00
}}
WIKI);

        $this->assertNull($alphaWins[0]->titleName);
        $this->assertSame(
            $this->participantNames($alphaWins[0]),
            $this->participantNames($bravoWins[0]),
            'Non-title side ordering must not depend on who won.',
        );
        $this->assertSame(['Alpha'], $this->winnerNames($alphaWins[0]));
        $this->assertSame(['Bravo'], $this->winnerNames($bravoWins[0]));
    }

    public function test_non_title_match_order_is_stable_across_repeated_parses(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Sting (wrestler)|Sting]] defeated [[Vader]]
| stip1 = Singles match
| time1 = 12:00
}}
WIKI;

        $first = $this->parser->parse($wikitext);
        $second = $this->parser->parse($wikitext);

        $this->assertSame(
            $this->participantNames($first[0]),
            $this->participantNames($second[0]),
        );
        $this->assertSame($first[0]->winnerSide, $second[0]->winnerSide);
    }

    public function test_king_of_the_ring_2000_parses_full_card_including_six_man_main_event(): void
    {
        $wikitext = $this->kingOfTheRing2000Wikitext();

        $matches = $this->parser->parse($wikitext, 'King of the Ring (2000)', 'King Of The Ring 2000');

        $this->assertSame(11, $this->parser->countDeclaredMatches($wikitext, 'King of the Ring (2000)', 'King Of The Ring 2000'));
        $this->assertCount(11, $matches);

        $mainEvent = collect($matches)->firstWhere('cardOrder', 11);

        $this->assertNotNull($mainEvent);
        $this->assertSame('tag', $mainEvent->matchType);
        $this->assertSame('WWF Championship', $mainEvent->titleName);
        $this->assertStringContainsString('McMahon', $mainEvent->participants[0]['name']);
        $this->assertStringContainsString('The Rock', implode(' ', $this->winnerNames($mainEvent)));
    }

    public function test_throws_when_wikitable_declares_more_matches_than_can_be_parsed(): void
    {
        $wikitext = <<<'WIKI'
==Results==
{| class="wikitable"
|-
! No.
! Results
! Stipulations
! Times
|-
|1
|[[Winner]] defeated [[Loser]]
|Singles match
|10:00
|-
|2
|[[A]] vs. [[B]] ended in a draw
|Singles match
|5:00
|}
WIKI;

        $this->expectException(WikipediaMatchCountMismatchException::class);
        $this->expectExceptionMessage('Wikipedia lists 2 matches but only 1 were parsed successfully.');

        $this->parser->parse($wikitext);
    }

    /**
     * @return non-empty-string
     */
    private function kingOfTheRing2000Wikitext(): string
    {
        return <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1 = [[Rikishi (wrestler)|Rikishi]] defeated [[Chris Benoit]] by disqualification
|stip1 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time1 = 3:25
|match2 = [[Val Venis]] (with [[Trish Stratus]]) defeated [[Eddie Guerrero]] (with [[Chyna]])
|stip2 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time2 = 8:04
|match3 = [[Crash Holly]] defeated [[Bull Buchanan]]
|stip3 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time3 = 4:07
|match4 = [[Kurt Angle]] defeated [[Chris Jericho]]
|stip4 = [[King of the Ring tournament|King of the Ring]] quarter-final match
|time4 = 9:50
|match5 = [[Edge and Christian]] defeated [[Too Cool]] ([[Grand Master Sexay]] and [[Scotty 2 Hotty]]) (c), [[The Hardy Boyz]] ([[Jeff Hardy]] and [[Matt Hardy]]), and [[T & A (professional wrestling)|T & A]] ([[Matt Bloom|Albert]] and [[Test (wrestler)|Test]])
|stip5 = [[Elimination match|Fatal 4-Way elimination match]] for the [[WWF Tag Team Championship]]
|time5 = 14:11
|match6 = [[Rikishi (wrestler)|Rikishi]] defeated [[Val Venis]] (with [[Trish Stratus]])
|stip6 = [[King of the Ring tournament|King of the Ring]] semi-final match
|time6 = 3:15
|match7 = [[Kurt Angle]] defeated [[Crash Holly]]
|stip7 = [[King of the Ring tournament|King of the Ring]] semi-final match
|time7 = 3:58
|match8 = [[Pat Patterson (wrestler)|Pat Patterson]] (c) vs. [[Gerald Brisco]] ended when [[Crash Holly]] pinned Patterson
|stip8 = [[Hardcore match|Hardcore]] [[Evening gown match]] for the [[WWF Hardcore Championship]]
|time8 = 3:07
|match9 = [[D-Generation X]] ([[Tori (wrestler)|Tori]], [[Road Dogg]] and [[X-Pac]]) defeated [[The Dudley Boyz]] ([[Bubba Ray Dudley]] and [[D-Von Dudley]])
|stip9 = [[Handicap match|Handicap]] [[Professional wrestling match types#Tables match|Tables]] [[Professional wrestling match types#Dumpster match|Dumpster match]]
|time9 = 9:45
|match10 = [[Kurt Angle]] defeated [[Rikishi (wrestler)|Rikishi]]
|stip10 = [[King of the Ring tournament|King of the Ring]] final match
|time10 = 5:56
|match11 = [[Dwayne Johnson|The Rock]] and [[The Brothers of Destruction]] ([[Kane (wrestler)|Kane]] and [[The Undertaker]]) defeated The McMahon-Helmsley Faction ([[Mr. McMahon]], [[Shane McMahon]] and [[Triple H]] (c)) (with [[Stephanie McMahon-Helmsley]])
|stip11 = [[Six-man tag team match]] for the [[WWF Championship]]
|time11 = 17:54
}}
WIKI;
    }
}
