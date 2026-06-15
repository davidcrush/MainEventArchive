<?php

namespace Tests\Unit;

use App\Services\Wikipedia\WikipediaVenueInfoboxParser;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class WikipediaVenueInfoboxParserTest extends TestCase
{
    private WikipediaVenueInfoboxParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->parser = app(WikipediaVenueInfoboxParser::class);
    }

    public function test_parses_stadium_infobox_with_former_names_and_capacity(): void
    {
        $metadata = $this->parser->parse(<<<'WIKI'
{{Infobox stadium
| stadium_name = Nashville Municipal Auditorium
| city = Nashville
| state = Tennessee
| country = United States
| capacity = 9,700
| former names = Municipal Auditorium
}}
WIKI, 'Nashville Municipal Auditorium');

        $this->assertSame('Nashville Municipal Auditorium', $metadata->name);
        $this->assertSame('Nashville', $metadata->city);
        $this->assertSame('Tennessee', $metadata->stateProvince);
        $this->assertSame('US', $metadata->country);
        $this->assertSame(9700, $metadata->capacity);
        $this->assertSame(['Municipal Auditorium'], $metadata->formerNames);
    }

    #[DataProvider('locationStringProvider')]
    public function test_parses_location_string(string $location, ?string $city, ?string $state, ?string $country): void
    {
        $metadata = $this->parser->parse(<<<WIKI
{{Infobox venue
| location = {$location}
}}
WIKI, 'Example Arena');

        $this->assertSame($city, $metadata->city);
        $this->assertSame($state, $metadata->stateProvince);
        $this->assertSame($country, $metadata->country);
    }

    /**
     * @return array<string, array{0: string, 1: ?string, 2: ?string, 3: ?string}>
     */
    public static function locationStringProvider(): array
    {
        return [
            'city state country' => ['Daytona Beach, Florida, United States', 'Daytona Beach', 'Florida', 'US'],
            'city state' => ['Baltimore, Maryland', 'Baltimore', 'Maryland', 'US'],
            'single value' => ['Jacksonville', 'Jacksonville', null, null],
        ];
    }

    public function test_returns_fallback_name_when_infobox_missing(): void
    {
        $metadata = $this->parser->parse('==Lead==', 'Ocean Center');

        $this->assertSame('Ocean Center', $metadata->name);
        $this->assertNull($metadata->capacity);
    }

    public function test_normalizes_city_with_address_before_br_tag(): void
    {
        $metadata = $this->parser->parse(<<<'WIKI'
{{Infobox venue
| city = 100 West Oglethorpe Boulevard<br />Albany
| state = Georgia 31701-6808
}}
WIKI, 'Albany Civic Center');

        $this->assertSame('Albany', $metadata->city);
        $this->assertSame('Georgia', $metadata->stateProvince);
        $this->assertSame('US', $metadata->country);
    }
}
