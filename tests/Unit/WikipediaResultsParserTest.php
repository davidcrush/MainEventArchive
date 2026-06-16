<?php

namespace Tests\Unit;

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
        $this->assertSame('Chris Benoit', $matches[0]->participants[0]['name']);
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
        $this->assertSame('Harlem Heat (Booker T & Stevie Ray)', $matches[0]->participants[0]['name']);

        $this->assertSame('Dick Slater & Bunkhouse Buck', $matches[1]->participants[0]['name']);
        $this->assertSame('Frankie Lancaster & Barry Houston', $matches[1]->participants[1]['name']);

        $this->assertSame('disqualification', $matches[2]->finish);
        $this->assertSame('Jim Duggan', $matches[2]->participants[0]['name']);

        $this->assertSame('Sting', $matches[3]->participants[0]['name']);
        $this->assertSame('Meng', $matches[3]->participants[1]['name']);
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
}
