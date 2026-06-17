<?php

namespace Tests\Unit;

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
