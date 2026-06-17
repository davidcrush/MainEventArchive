<?php

namespace Tests\Unit;

use App\Services\Fandom\FandomNitroInfoboxParser;
use Tests\TestCase;

class FandomNitroInfoboxParserTest extends TestCase
{
    private FandomNitroInfoboxParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(FandomNitroInfoboxParser::class);
    }

    public function test_extracts_venue_and_city_from_infobox(): void
    {
        $wikitext = <<<'WIKI'
{{Infobox Wrestling episode
| name = Monday Nitro
| promotion = [[World Championship Wrestling]]
| date = [[January 1]], [[1996]]
| venue = [[The Omni]]
| city = [[Atlanta, Georgia]]
}}
The January 1, 1996 edition.
WIKI;

        $result = $this->parser->parse($wikitext);

        $this->assertSame('The Omni', $result['venue']);
        $this->assertSame('Atlanta, Georgia', $result['city']);
    }

    public function test_returns_null_for_missing_fields(): void
    {
        $result = $this->parser->parse('{{Infobox Wrestling episode\n| name = Monday Nitro\n}}');

        $this->assertNull($result['venue']);
        $this->assertNull($result['city']);
    }
}
