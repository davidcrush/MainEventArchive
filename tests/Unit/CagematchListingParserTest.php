<?php

namespace Tests\Unit;

use App\Services\Cagematch\CagematchListingParser;
use Tests\TestCase;

class CagematchListingParserTest extends TestCase
{
    private CagematchListingParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new CagematchListingParser;
    }

    public function test_parses_wcw_ppv_listing_fixture(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/cagematch/wcw-ppv-listing.html'));

        $events = $this->parser->parse($html);

        $this->assertCount(4, $events);

        $this->assertSame(1001, $events[0]->eventId);
        $this->assertSame('Starrcade', $events[0]->title);
        $this->assertSame('1996-12-29', $events[0]->date->toDateString());

        $this->assertSame(1002, $events[1]->eventId);
        $this->assertSame('Bash At The Beach', $events[1]->title);
        $this->assertSame('1996-07-07', $events[1]->date->toDateString());

        $this->assertSame(1003, $events[2]->eventId);
        $this->assertSame('Halloween Havoc', $events[2]->title);
        $this->assertSame('1995-10-29', $events[2]->date->toDateString());
    }

    public function test_returns_empty_list_when_no_event_links_present(): void
    {
        $events = $this->parser->parse('<html><body><p>No events here</p></body></html>');

        $this->assertSame([], $events);
    }
}
