<?php

namespace Tests\Unit;

use App\Services\YouTube\YouTubeTitleParser;
use PHPUnit\Framework\TestCase;

class YouTubeTitleParserTest extends TestCase
{
    private YouTubeTitleParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = new YouTubeTitleParser;
    }

    public function test_parses_full_event_title_with_year(): void
    {
        $parsed = $this->parser->parse(
            'FULL EVENT: WCW Halloween Havoc 1998 – Goldberg vs. DDP, Hogan vs. Warrior 2 and more!',
        );

        $this->assertSame('Halloween Havoc 1998', $parsed['eventTitle']);
        $this->assertSame(1998, $parsed['year']);
    }

    public function test_parses_full_event_title_with_pipe_separator(): void
    {
        $parsed = $this->parser->parse(
            'FULL EVENT: WCW SuperBrawl V | Hulk Hogan vs. Vader, Sting & Savage vs. Big Bubba & Avalanche',
        );

        $this->assertSame('SuperBrawl V', $parsed['eventTitle']);
        $this->assertNull($parsed['year']);
    }

    public function test_parses_great_american_bash_with_year(): void
    {
        $parsed = $this->parser->parse(
            'FULL EVENT: WCW Great American Bash 1990 | Sting vs. Ric Flair; Vader debuts',
        );

        $this->assertSame('Great American Bash 1990', $parsed['eventTitle']);
        $this->assertSame(1990, $parsed['year']);
    }

    public function test_identifies_full_event_prefix(): void
    {
        $this->assertTrue($this->parser->isFullEventTitle('FULL EVENT: WCW Fall Brawl 1995 | WarGames'));
        $this->assertFalse($this->parser->isFullEpisodeTitle('FULL EVENT: WCW Fall Brawl 1995 | WarGames'));
        $this->assertTrue($this->parser->isFullEpisodeTitle('FULL EPISODE: WCW Monday Nitro, Feb. 9, 1998'));
        $this->assertFalse($this->parser->isFullEventTitle('FULL EPISODE: WCW Monday Nitro, Feb. 9, 1998'));
    }

    public function test_parses_nitro_air_date_from_full_episode_title(): void
    {
        $airDate = $this->parser->parseNitroAirDate(
            'FULL EPISODE: Scott Hall declares war on WCW: WCW Monday Nitro, May 27, 1996',
        );

        $this->assertNotNull($airDate);
        $this->assertSame('1996-05-27', $airDate->toDateString());
    }

    public function test_parses_wwe_full_event_title_with_year(): void
    {
        $parsed = $this->parser->parse(
            'FULL EVENT: Survivor Series 2001 | Team WWF vs. Team Alliance and MORE!',
        );

        $this->assertSame('Survivor Series 2001', $parsed['eventTitle']);
        $this->assertSame(2001, $parsed['year']);
    }

    public function test_strips_wwe_prefix_from_event_title(): void
    {
        $parsed = $this->parser->parse(
            'FULL EVENT: WWE Backlash 2008 | Cena vs. Orton vs. Triple H vs. JBL, Batista vs. Cena',
        );

        $this->assertSame('Backlash 2008', $parsed['eventTitle']);
        $this->assertSame(2008, $parsed['year']);
    }

    public function test_syncable_title_includes_full_episode_when_enabled(): void
    {
        $title = 'FULL EPISODE: Hogan vs. Savage: WCW Monday Nitro, Feb. 9, 1998';

        $this->assertFalse($this->parser->isSyncableTitle($title));
        $this->assertTrue($this->parser->isSyncableTitle($title, includeFullEpisodes: true));
    }
}
