<?php

namespace Tests\Unit;

use App\Services\Streaming\NetflixSavedHtmlParser;
use App\Services\Streaming\NetflixUrlParser;
use InvalidArgumentException;
use Tests\TestCase;

class NetflixUrlParserTest extends TestCase
{
    public function test_parses_watch_url(): void
    {
        $reference = app(NetflixUrlParser::class)->parse('https://www.netflix.com/watch/80117477');

        $this->assertSame('80117477', $reference['external_id']);
        $this->assertSame('https://www.netflix.com/watch/80117477', $reference['url']);
    }

    public function test_parses_title_url(): void
    {
        $reference = app(NetflixUrlParser::class)->parse('https://www.netflix.com/title/80117477');

        $this->assertSame('80117477', $reference['external_id']);
    }

    public function test_parses_numeric_title_id(): void
    {
        $reference = app(NetflixUrlParser::class)->parse('80117477');

        $this->assertSame('80117477', $reference['external_id']);
    }

    public function test_rejects_invalid_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(NetflixUrlParser::class)->parse('not-a-netflix-link');
    }
}

class NetflixSavedHtmlParserTest extends TestCase
{
    public function test_parses_title_links_from_saved_html(): void
    {
        $html = <<<'HTML'
<a aria-label="Survivor Series 2001" href="/title/80117477">Survivor Series 2001</a>
<a href="https://www.netflix.com/watch/81234567" aria-label="Royal Rumble 1991">Royal Rumble 1991</a>
HTML;

        $entries = app(NetflixSavedHtmlParser::class)->parse($html);

        $this->assertCount(2, $entries);
        $this->assertSame('80117477', $entries[0]->titleId);
        $this->assertSame('Survivor Series 2001', $entries[0]->title);
        $this->assertSame('81234567', $entries[1]->titleId);
    }
}
