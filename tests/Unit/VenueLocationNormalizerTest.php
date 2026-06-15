<?php

namespace Tests\Unit;

use App\Services\VenueLocationNormalizer;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class VenueLocationNormalizerTest extends TestCase
{
    private VenueLocationNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new VenueLocationNormalizer;
    }

    #[DataProvider('normalizationProvider')]
    public function test_normalizes_venue_location_fields(
        ?string $city,
        ?string $state,
        ?string $country,
        ?string $expectedCity,
        ?string $expectedState,
        ?string $expectedCountry,
    ): void {
        [$normalizedCity, $normalizedState, $normalizedCountry] = $this->normalizer->normalize(
            $city,
            $state,
            $country,
        );

        $this->assertSame($expectedCity, $normalizedCity);
        $this->assertSame($expectedState, $normalizedState);
        $this->assertSame($expectedCountry, $normalizedCountry);
    }

    /**
     * @return array<string, array{0: ?string, 1: ?string, 2: ?string, 3: ?string, 4: ?string, 5: ?string}>
     */
    public static function normalizationProvider(): array
    {
        return [
            'city with br tag' => [
                '100 West Oglethorpe Boulevard<br />Albany',
                'Georgia 31701-6808',
                null,
                'Albany',
                'Georgia',
                'US',
            ],
            'city with br no slash' => [
                '710 Williamson Road Northeast<br>Roanoke',
                'VA 24016',
                null,
                'Roanoke',
                'Virginia',
                'US',
            ],
            'country label variants' => [
                'Chicago',
                'Illinois',
                'United States',
                'Chicago',
                'Illinois',
                'US',
            ],
            'country u s abbreviation' => [
                'Baltimore',
                'Maryland',
                'U.S.',
                'Baltimore',
                'Maryland',
                'US',
            ],
            'state only infers country' => [
                'Baltimore',
                'Maryland',
                null,
                'Baltimore',
                'Maryland',
                'US',
            ],
            'state with zip code' => [
                'Albany',
                'Georgia 31701-6808',
                null,
                'Albany',
                'Georgia',
                'US',
            ],
            'country with state and zip' => [
                '375 East Main Street',
                'Tupelo',
                'Mississippi 38804',
                'Tupelo',
                'Mississippi',
                'US',
            ],
            'state with br and country suffix' => [
                'Atlanta',
                'Georgia 30303<br>United States',
                null,
                'Atlanta',
                'Georgia',
                'US',
            ],
            'zip only country infers us' => [
                'Biloxi',
                'Mississippi',
                '39531',
                'Biloxi',
                'Mississippi',
                'US',
            ],
            'duplicate city after br' => [
                '2350 Beach Boulevard<br>Biloxi Biloxi',
                'Mississippi',
                null,
                'Biloxi',
                'Mississippi',
                'US',
            ],
            'non us values unchanged' => [
                'Toronto',
                'Ontario',
                'Canada',
                'Toronto',
                'Ontario',
                'Canada',
            ],
        ];
    }
}
