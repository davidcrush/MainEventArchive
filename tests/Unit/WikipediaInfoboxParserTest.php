<?php

namespace Tests\Unit;

use App\Services\Wikipedia\WikipediaInfoboxParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WikipediaInfoboxParserTest extends TestCase
{
    private WikipediaInfoboxParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new WikipediaInfoboxParser;
    }

    public function test_parses_bash_at_the_beach_1996_metadata(): void
    {
        $metadata = $this->parser->parse($this->bashAtTheBeach1996Infobox());

        $this->assertSame('Ocean Center', $metadata->venue);
        $this->assertSame('Daytona Beach, Florida', $metadata->city);
        $this->assertSame(8300, $metadata->attendance);
    }

    public function test_parses_starrcade_1996_attendance(): void
    {
        $metadata = $this->parser->parse($this->starrcade1996Infobox());

        $this->assertSame('Nashville Municipal Auditorium', $metadata->venue);
        $this->assertSame('Nashville, Tennessee', $metadata->city);
        $this->assertSame(9030, $metadata->attendance);
    }

    public function test_parses_wrestlemania_2_multi_venue_metadata(): void
    {
        $metadata = $this->parser->parse($this->wrestleMania2Infobox());

        $this->assertSame(
            'Nassau Veterans Memorial Coliseum, Rosemont Horizon, Los Angeles Memorial Sports Arena',
            $metadata->venue,
        );
        $this->assertSame(
            'Uniondale, New York, Rosemont, Illinois, Los Angeles, California',
            $metadata->city,
        );
        $this->assertSame(40085, $metadata->attendance);
    }

    #[DataProvider('unparseableAttendanceProvider')]
    public function test_returns_null_attendance_when_unparseable(string $wikitext): void
    {
        $metadata = $this->parser->parse($wikitext);

        $this->assertNull($metadata->attendance);
    }

    /**
     * @return array<string, array{0: string}>
     */
    public static function unparseableAttendanceProvider(): array
    {
        return [
            'sold out' => [<<<'WIKI'
{{Infobox wrestling event
|name = Example Event
|attendance = Sold out
}}
WIKI],
            'missing infobox' => ['==Results=='],
            'missing attendance param' => [<<<'WIKI'
{{Infobox wrestling event
|name = Example Event
|venue = [[Example Arena]]
|city = [[Example City]]
}}
WIKI],
        ];
    }

    public function test_returns_null_fields_when_infobox_missing(): void
    {
        $metadata = $this->parser->parse('==Results==');

        $this->assertNull($metadata->venue);
        $this->assertNull($metadata->city);
        $this->assertNull($metadata->attendance);
    }

    public function test_strips_html_comments_from_city_field(): void
    {
        $metadata = $this->parser->parse(<<<'WIKI'
{{Infobox wrestling event
|name = Uncensored (1997)
|venue = [[North Charleston Coliseum]]
|city = <!--Please do not replace-->[[Charleston, South Carolina]]<!--end-->
|attendance = 9,285
}}
==Results==
WIKI);

        $this->assertSame('North Charleston Coliseum', $metadata->venue);
        $this->assertSame('Charleston, South Carolina', $metadata->city);
        $this->assertSame(9285, $metadata->attendance);
    }

    public function test_extracts_single_venue_wikilink(): void
    {
        $metadata = $this->parser->parse(<<<'WIKI'
{{Infobox wrestling event
|venue = [[Ocean Center]]<ref name=pwh/>
|city = [[Daytona Beach, Florida]]
}}
==Results==
WIKI);

        $this->assertCount(1, $metadata->venueLinks);
        $this->assertSame('Ocean Center', $metadata->venueLinks[0]->pageTitle);
        $this->assertSame('Ocean Center', $metadata->venueLinks[0]->displayName);
    }

    public function test_extracts_piped_venue_wikilink_display_name(): void
    {
        $metadata = $this->parser->parse(<<<'WIKI'
{{Infobox wrestling event
|venue = [[Allstate Arena|Rosemont Horizon]]
}}
==Results==
WIKI);

        $this->assertCount(1, $metadata->venueLinks);
        $this->assertSame('Allstate Arena', $metadata->venueLinks[0]->pageTitle);
        $this->assertSame('Rosemont Horizon', $metadata->venueLinks[0]->displayName);
    }

    public function test_extracts_multiple_venue_links_for_multi_venue_show(): void
    {
        $metadata = $this->parser->parse($this->wrestleMania2Infobox());

        $this->assertCount(3, $metadata->venueLinks);
        $this->assertSame('Nassau Veterans Memorial Coliseum', $metadata->venueLinks[0]->pageTitle);
        $this->assertSame('Allstate Arena', $metadata->venueLinks[1]->pageTitle);
        $this->assertSame('Rosemont Horizon', $metadata->venueLinks[1]->displayName);
    }

    private function bashAtTheBeach1996Infobox(): string
    {
        return <<<'WIKI'
{{Infobox wrestling event
|name       = Bash at the Beach (1996)
|date       = July 7, 1996<ref name=pwh>{{cite web|url=https://www.prowrestlinghistory.com/supercards/usa/wcw/beach.html#96|title=Bash at the Beach 1996|publisher=Pro Wrestling History|access-date=March 29, 2008}}</ref>
|venue      = [[Ocean Center]]<ref name=pwh/>
|city       = [[Daytona Beach, Florida]]<ref name=pwh/>
|attendance = 8,300<ref name=pwh/>
}}
==Results==
WIKI;
    }

    private function starrcade1996Infobox(): string
    {
        return <<<'WIKI'
{{Infobox wrestling event
|name       = Starrcade
|date=December 29, 1996<ref name=411mania/>
|venue      = [[Nashville Municipal Auditorium]]<ref name=411mania/>
|city       = [[Nashville, Tennessee]]<ref name=411mania/>
|attendance = 9,030
}}
==Results==
WIKI;
    }

    private function wrestleMania2Infobox(): string
    {
        return <<<'WIKI'
{{Infobox Wrestling event
| name      = WrestleMania 2
| date  = April 7, 1986<ref name="pwh">{{cite web|url=https://www.prowrestlinghistory.com/supercards/usa/wwf/mania.html#II|title=WrestleMania II results|work=Wrestling Supercards and Tournaments|access-date=May 25, 2008}}</ref>
| venue =
*[[Nassau Veterans Memorial Coliseum]]<ref name="pwh"/>
*[[Allstate Arena|Rosemont Horizon]]<ref name="pwh"/>
*[[Los Angeles Memorial Sports Arena]]<ref name="pwh"/>
| city =
*[[Uniondale, New York]]<ref name="pwh"/>
*[[Rosemont, Illinois]]<ref name="pwh"/>
*[[Los Angeles, California]]<ref name="pwh"/>
| attendance = 40,085 (combined)<ref>{{cite web|url=https://www.wwe.com/shows/wrestlemania/2/mainevent|title=Wrestlemania 2 main event|work=[[WWE]]|access-date=January 23, 2014}}</ref>
}}
==Results==
WIKI;
    }
}
