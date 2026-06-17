<?php

namespace Tests\Unit;

use App\Services\Fandom\FandomNitroCatalogParser;
use Tests\TestCase;

class FandomNitroCatalogParserTest extends TestCase
{
    private FandomNitroCatalogParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(FandomNitroCatalogParser::class);
    }

    private function navbox(): string
    {
        return <<<'WIKI'
{| class="toccolours collapsible"
!style="background:#ccccff"|'''List of [[WCW Monday Nitro|Monday Nitro]] results'''
|-
!style="background:#ccccff"|'''[[1995 List of Monday Nitro results]]'''
|-
| align="center" | [[September 4, 1995 Monday Nitro results|9/4]] • [[September 11, 1995 Monday Nitro results|9/11]] • [[September 18, 1995 Monday Nitro results|9/18]]
|-
!style="background:#ccccff"|'''[[1996 List of Monday Nitro results]]'''
|-
| align="center" | [[January 1, 1996 Monday Nitro results|1/1]] • [[January 8, 1996 Monday Nitro results|1/8]]
|}
[[Category:WCW Monday Nitro results]]
WIKI;
    }

    public function test_parses_episodes_in_chronological_order_with_sequential_numbers(): void
    {
        $episodes = $this->parser->parse($this->navbox());

        $this->assertCount(5, $episodes);

        $this->assertSame('September 4, 1995 Monday Nitro results', $episodes[0]['pageTitle']);
        $this->assertSame('1995-09-04', $episodes[0]['date']);
        $this->assertSame(1, $episodes[0]['episodeNumber']);

        $this->assertSame('1996-01-01', $episodes[3]['date']);
        $this->assertSame(4, $episodes[3]['episodeNumber']);
        $this->assertSame('1996-01-08', $episodes[4]['date']);
        $this->assertSame(5, $episodes[4]['episodeNumber']);
    }

    public function test_ignores_year_index_header_links(): void
    {
        $episodes = $this->parser->parse($this->navbox());

        $titles = array_column($episodes, 'pageTitle');

        $this->assertNotContains('1995 List of Monday Nitro results', $titles);
        $this->assertNotContains('1996 List of Monday Nitro results', $titles);
    }

    public function test_deduplicates_repeated_links(): void
    {
        $wikitext = '[[January 1, 1996 Monday Nitro results|1/1]] [[January 1, 1996 Monday Nitro results]]';

        $episodes = $this->parser->parse($wikitext);

        $this->assertCount(1, $episodes);
        $this->assertSame(1, $episodes[0]['episodeNumber']);
    }
}
