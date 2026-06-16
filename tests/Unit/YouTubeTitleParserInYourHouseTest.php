<?php

namespace Tests\Unit;

use App\Services\YouTube\YouTubeTitleParser;
use PHPUnit\Framework\TestCase;

class YouTubeTitleParserInYourHouseTest extends TestCase
{
    public function test_parses_in_your_house_subtitle_format(): void
    {
        $parser = new YouTubeTitleParser;

        $parsed = $parser->parseInYourHouse('In Your House - Final Four');

        $this->assertSame([
            'number' => null,
            'subtitle' => 'Final Four',
        ], $parsed);
    }

    public function test_parses_in_your_house_number_format(): void
    {
        $parser = new YouTubeTitleParser;

        $parsed = $parser->parseInYourHouse('In Your House #6');

        $this->assertSame([
            'number' => 6,
            'subtitle' => null,
        ], $parsed);
    }
}
