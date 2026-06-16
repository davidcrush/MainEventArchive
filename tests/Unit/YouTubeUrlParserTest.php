<?php

namespace Tests\Unit;

use App\Services\YouTube\YouTubeUrlParser;
use InvalidArgumentException;
use Tests\TestCase;

class YouTubeUrlParserTest extends TestCase
{
    public function test_parses_watch_url(): void
    {
        $reference = app(YouTubeUrlParser::class)->parse('https://www.youtube.com/watch?v=ftPK-rYz7Vc');

        $this->assertSame('ftPK-rYz7Vc', $reference['external_id']);
        $this->assertSame('https://www.youtube.com/watch?v=ftPK-rYz7Vc', $reference['url']);
    }

    public function test_parses_youtu_be_url(): void
    {
        $reference = app(YouTubeUrlParser::class)->parse('https://youtu.be/abc12345678');

        $this->assertSame('abc12345678', $reference['external_id']);
        $this->assertSame('https://www.youtube.com/watch?v=abc12345678', $reference['url']);
    }

    public function test_parses_bare_video_id(): void
    {
        $reference = app(YouTubeUrlParser::class)->parse('abc12345678');

        $this->assertSame('abc12345678', $reference['external_id']);
    }

    public function test_rejects_invalid_input(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(YouTubeUrlParser::class)->parse('not-a-youtube-url');
    }
}
