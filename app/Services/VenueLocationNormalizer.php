<?php

namespace App\Services;

class VenueLocationNormalizer
{
    /** @var array<string, string> lowercase lookup => canonical state/province label */
    private const US_STATES = [
        'al' => 'Alabama',
        'alabama' => 'Alabama',
        'ak' => 'Alaska',
        'alaska' => 'Alaska',
        'az' => 'Arizona',
        'arizona' => 'Arizona',
        'ar' => 'Arkansas',
        'arkansas' => 'Arkansas',
        'ca' => 'California',
        'california' => 'California',
        'co' => 'Colorado',
        'colorado' => 'Colorado',
        'ct' => 'Connecticut',
        'connecticut' => 'Connecticut',
        'de' => 'Delaware',
        'delaware' => 'Delaware',
        'dc' => 'District of Columbia',
        'district of columbia' => 'District of Columbia',
        'fl' => 'Florida',
        'florida' => 'Florida',
        'ga' => 'Georgia',
        'georgia' => 'Georgia',
        'hi' => 'Hawaii',
        'hawaii' => 'Hawaii',
        'id' => 'Idaho',
        'idaho' => 'Idaho',
        'il' => 'Illinois',
        'illinois' => 'Illinois',
        'in' => 'Indiana',
        'indiana' => 'Indiana',
        'ia' => 'Iowa',
        'iowa' => 'Iowa',
        'ks' => 'Kansas',
        'kansas' => 'Kansas',
        'ky' => 'Kentucky',
        'kentucky' => 'Kentucky',
        'la' => 'Louisiana',
        'louisiana' => 'Louisiana',
        'me' => 'Maine',
        'maine' => 'Maine',
        'md' => 'Maryland',
        'maryland' => 'Maryland',
        'ma' => 'Massachusetts',
        'massachusetts' => 'Massachusetts',
        'mi' => 'Michigan',
        'michigan' => 'Michigan',
        'mn' => 'Minnesota',
        'minnesota' => 'Minnesota',
        'ms' => 'Mississippi',
        'mississippi' => 'Mississippi',
        'mo' => 'Missouri',
        'missouri' => 'Missouri',
        'mt' => 'Montana',
        'montana' => 'Montana',
        'ne' => 'Nebraska',
        'nebraska' => 'Nebraska',
        'nv' => 'Nevada',
        'nevada' => 'Nevada',
        'nh' => 'New Hampshire',
        'new hampshire' => 'New Hampshire',
        'nj' => 'New Jersey',
        'new jersey' => 'New Jersey',
        'nm' => 'New Mexico',
        'new mexico' => 'New Mexico',
        'ny' => 'New York',
        'new york' => 'New York',
        'nc' => 'North Carolina',
        'north carolina' => 'North Carolina',
        'nd' => 'North Dakota',
        'north dakota' => 'North Dakota',
        'oh' => 'Ohio',
        'ohio' => 'Ohio',
        'ok' => 'Oklahoma',
        'oklahoma' => 'Oklahoma',
        'or' => 'Oregon',
        'oregon' => 'Oregon',
        'pa' => 'Pennsylvania',
        'pennsylvania' => 'Pennsylvania',
        'ri' => 'Rhode Island',
        'rhode island' => 'Rhode Island',
        'sc' => 'South Carolina',
        'south carolina' => 'South Carolina',
        'sd' => 'South Dakota',
        'south dakota' => 'South Dakota',
        'tn' => 'Tennessee',
        'tennessee' => 'Tennessee',
        'tx' => 'Texas',
        'texas' => 'Texas',
        'ut' => 'Utah',
        'utah' => 'Utah',
        'vt' => 'Vermont',
        'vermont' => 'Vermont',
        'va' => 'Virginia',
        'virginia' => 'Virginia',
        'wa' => 'Washington',
        'washington' => 'Washington',
        'wv' => 'West Virginia',
        'west virginia' => 'West Virginia',
        'wi' => 'Wisconsin',
        'wisconsin' => 'Wisconsin',
        'wy' => 'Wyoming',
        'wyoming' => 'Wyoming',
    ];

    /** @var list<string> */
    private const US_COUNTRY_VALUES = [
        'us',
        'u.s.',
        'u.s',
        'u.s.a.',
        'u.s.a',
        'usa',
        'united states',
        'united states of america',
    ];

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    public function normalize(?string $city, ?string $stateProvince, ?string $country): array
    {
        $city = $this->normalizeCity($city);

        if ($city !== null) {
            $city = $this->dedupeRepeatedCityName($city);
        }
        $stateFromCountry = $this->matchUsState($country);

        $countryFromState = $this->extractCountryFromBrSegment($stateProvince);
        if (($country === null || trim($country) === '') && $countryFromState !== null) {
            $country = $countryFromState;
        }

        $stateProvince = $this->normalizeStateProvince(
            $this->extractPrimarySegmentBeforeBr($stateProvince),
        );
        $country = $this->normalizeCountry($country);

        if ($stateFromCountry !== null) {
            $country = 'US';

            if (! $this->isUsState($stateProvince)) {
                if ($this->looksLikeStreetAddress($city ?? '') && $stateProvince !== null && $stateProvince !== '') {
                    $city = $stateProvince;
                }

                $stateProvince = $stateFromCountry;
            }
        } elseif ($country === null && $this->isUsState($stateProvince)) {
            $country = 'US';
        }

        return [$city, $stateProvince, $country];
    }

    public function isUsState(?string $value): bool
    {
        return $this->matchUsState($value) !== null;
    }

    private function normalizeCity(?string $city): ?string
    {
        if ($city === null || trim($city) === '') {
            return null;
        }

        $segments = preg_split('/<br\s*\/?>/i', $city) ?: [];

        if (count($segments) > 1) {
            $city = trim((string) end($segments));
        }

        $city = trim($city);

        return $city === '' ? null : $city;
    }

    private function dedupeRepeatedCityName(string $city): string
    {
        $parts = preg_split('/\s+/', trim($city)) ?: [];

        if (count($parts) === 2 && strcasecmp($parts[0], $parts[1]) === 0) {
            return $parts[0];
        }

        return trim($city);
    }

    private function extractPrimarySegmentBeforeBr(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $segments = preg_split('/<br\s*\/?>/i', $value) ?: [];

        return trim((string) ($segments[0] ?? $value));
    }

    private function extractCountryFromBrSegment(?string $value): ?string
    {
        if ($value === null || preg_match('/<br\s*\/?>/i', $value) !== 1) {
            return null;
        }

        $segments = preg_split('/<br\s*\/?>/i', $value) ?: [];
        $suffix = trim((string) end($segments));

        if ($suffix === '' || preg_match('/^\d{5}(?:-\d{4})?$/', $suffix) === 1) {
            return null;
        }

        return $suffix;
    }

    private function normalizeStateProvince(?string $stateProvince): ?string
    {
        if ($stateProvince === null || trim($stateProvince) === '') {
            return null;
        }

        $stateProvince = $this->stripZipCode(trim($stateProvince));
        $matchedState = $this->matchUsState($stateProvince);

        if ($matchedState !== null) {
            return $matchedState;
        }

        return $stateProvince === '' ? null : $stateProvince;
    }

    private function normalizeCountry(?string $country): ?string
    {
        if ($country === null || trim($country) === '') {
            return null;
        }

        $country = $this->stripZipCode(trim($country));

        if (preg_match('/^\d{5}(?:-\d{4})?$/', $country) === 1) {
            return null;
        }

        if ($this->matchUsState($country) !== null) {
            return 'US';
        }

        if ($this->isUsCountryLabel($country)) {
            return 'US';
        }

        return $country === '' ? null : $country;
    }

    private function matchUsState(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        $stripped = $this->stripZipCode(trim($value));
        $key = strtolower($stripped);

        return self::US_STATES[$key] ?? null;
    }

    private function stripZipCode(string $value): string
    {
        $value = preg_replace('/\s+\d{5}(?:-\d{4})?\s*$/', '', $value) ?? $value;
        $value = preg_replace('/\s+[A-Z]{2}\s+\d{5}(?:-\d{4})?\s*$/', '', $value) ?? $value;

        return trim($value);
    }

    private function isUsCountryLabel(string $value): bool
    {
        return in_array(strtolower(trim($value)), self::US_COUNTRY_VALUES, true);
    }

    private function looksLikeStreetAddress(string $value): bool
    {
        if (preg_match('/^\d+\s/', $value) === 1) {
            return true;
        }

        return preg_match('/\b(street|st\.?|road|rd\.?|boulevard|blvd\.?|avenue|ave\.?|drive|dr\.?|lane|ln\.?|way|highway|hwy\.?|parkway|pkwy\.?|circle|court|ct\.?|place|pl\.?)\b/i', $value) === 1;
    }
}
