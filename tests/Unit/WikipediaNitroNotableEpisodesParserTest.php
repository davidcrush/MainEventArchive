<?php

namespace Tests\Unit;

use App\Services\Wikipedia\WikipediaNitroNotableEpisodesParser;
use Tests\TestCase;

class WikipediaNitroNotableEpisodesParserTest extends TestCase
{
    private WikipediaNitroNotableEpisodesParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(WikipediaNitroNotableEpisodesParser::class);
    }

    public function test_parses_notable_episodes_wikitable(): void
    {
        $wikitext = <<<'WIKI'
==Notable episodes==
{| class="wikitable"
|-
! Episode Title !! Date !! Venue !! Location !! Rating !! Note
|-
| WCW Monday Nitro || September 4, 1995 || Mall of America || Bloomington, Minnesota || 2.5 || First episode of Nitro.
|-
| WCW Monday Nitro || August 31, 1998 || Miami Arena || Miami, Florida || 6.0 || Nitro reaches its highest rated episode.
|}
WIKI;

        $episodes = $this->parser->parse($wikitext);

        $this->assertCount(2, $episodes);
        $this->assertSame('WCW Monday Nitro', $episodes[0]->episodeTitle);
        $this->assertSame('1995-09-04', $episodes[0]->date->toDateString());
        $this->assertSame('Mall of America', $episodes[0]->venue);
        $this->assertSame('Bloomington, Minnesota', $episodes[0]->city);
        $this->assertSame(2.5, $episodes[0]->tvRating);

        $this->assertSame('1998-08-31', $episodes[1]->date->toDateString());
        $this->assertSame(6.0, $episodes[1]->tvRating);
    }
}
