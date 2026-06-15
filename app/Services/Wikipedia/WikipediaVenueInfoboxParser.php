<?php

namespace App\Services\Wikipedia;

use App\Data\ParsedWikipediaVenueMetadata;
use App\Services\VenueLocationNormalizer;

class WikipediaVenueInfoboxParser
{
    private const INFOBOX_PATTERN = '/\{\{Infobox\s+(venue|stadium|indoor arena|convention center|arena|sports venue)/i';

    public function __construct(
        private readonly VenueLocationNormalizer $locationNormalizer,
    ) {}

    public function parse(string $wikitext, string $fallbackName): ParsedWikipediaVenueMetadata
    {
        $infoboxBody = $this->extractInfoboxBody($wikitext);

        if ($infoboxBody === null) {
            return new ParsedWikipediaVenueMetadata(
                name: $fallbackName,
                city: null,
                stateProvince: null,
                country: null,
                capacity: null,
            );
        }

        $parameters = $this->parseInfoboxParameters($infoboxBody);

        $name = $this->parseTextField($parameters, ['name', 'stadium name', 'venue name']) ?? $fallbackName;
        $city = $this->parseTextField($parameters, ['city', 'location city']);
        $stateProvince = $this->parseTextField($parameters, ['state', 'state/province', 'province']);
        $country = $this->parseTextField($parameters, ['country']);

        if ($city === null) {
            [$parsedCity, $parsedState, $parsedCountry] = $this->parseLocationString(
                $this->parseTextField($parameters, ['location', 'address', 'location place']),
            );
            $city ??= $parsedCity;
            $stateProvince ??= $parsedState;
            $country ??= $parsedCountry;
        }

        $capacity = $this->parseCapacity($parameters);
        $formerNames = $this->parseFormerNames($parameters);

        [$city, $stateProvince, $country] = $this->locationNormalizer->normalize(
            $city,
            $stateProvince,
            $country,
        );

        return new ParsedWikipediaVenueMetadata(
            name: $name,
            city: $city,
            stateProvince: $stateProvince,
            country: $country,
            capacity: $capacity,
            formerNames: $formerNames,
        );
    }

    private function extractInfoboxBody(string $wikitext): ?string
    {
        if (preg_match(self::INFOBOX_PATTERN, $wikitext, $match, PREG_OFFSET_CAPTURE) !== 1) {
            return null;
        }

        $start = $match[0][1];
        $length = strlen($wikitext);
        $depth = 0;
        $index = $start;

        while ($index < $length) {
            if (substr($wikitext, $index, 2) === '{{') {
                $depth++;
                $index += 2;

                continue;
            }

            if (substr($wikitext, $index, 2) === '}}') {
                $depth--;
                $index += 2;

                if ($depth === 0) {
                    $template = substr($wikitext, $start, $index - $start);

                    return $this->stripInfoboxOpening($template);
                }

                continue;
            }

            $index++;
        }

        return null;
    }

    private function stripInfoboxOpening(string $template): string
    {
        if (preg_match('/^\{\{Infobox\s+[^|]+/i', $template, $match) !== 1) {
            return $template;
        }

        return substr($template, strlen($match[0]));
    }

    /**
     * @return array<string, string>
     */
    private function parseInfoboxParameters(string $body): array
    {
        $parameters = [];
        $currentKey = null;
        $currentValue = [];

        foreach (explode("\n", $body) as $line) {
            if (trim($line) === '}}') {
                break;
            }

            if (preg_match('/^\s*\|([^=]+?)=(.*)$/', $line, $matches) === 1) {
                if ($currentKey !== null) {
                    $parameters[strtolower(trim($currentKey))] = trim(implode("\n", $currentValue));
                }

                $currentKey = trim($matches[1]);
                $currentValue = [trim($matches[2])];

                continue;
            }

            if ($currentKey !== null) {
                $currentValue[] = $line;
            }
        }

        if ($currentKey !== null) {
            $parameters[strtolower(trim($currentKey))] = trim(implode("\n", $currentValue));
        }

        return $parameters;
    }

    /**
     * @param  list<string>  $keys
     */
    private function parseTextField(array $parameters, array $keys): ?string
    {
        foreach ($keys as $key) {
            if (! isset($parameters[$key])) {
                continue;
            }

            $value = $this->stripWikiMarkup($parameters[$key]);

            if ($value !== '') {
                return $value;
            }
        }

        return null;
    }

    /**
     * @return array{0: ?string, 1: ?string, 2: ?string}
     */
    private function parseLocationString(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [null, null, null];
        }

        $cleaned = $this->stripWikiMarkup($raw);
        $parts = array_values(array_filter(array_map('trim', explode(',', $cleaned))));

        if ($parts === []) {
            return [null, null, null];
        }

        if (count($parts) === 1) {
            return [$parts[0], null, null];
        }

        if (count($parts) === 2) {
            return [$parts[0], $parts[1], null];
        }

        return [
            $parts[0],
            $parts[count($parts) - 2],
            $parts[count($parts) - 1],
        ];
    }

    /**
     * @param  array<string, string>  $parameters
     */
    private function parseCapacity(array $parameters): ?int
    {
        $raw = $this->parseTextField($parameters, [
            'capacity',
            'seating capacity',
            'capacity (concerts)',
            'capacity (wrestling)',
        ]);

        if ($raw === null) {
            return null;
        }

        if (preg_match('/(\d[\d,]*)/', $raw, $matches) !== 1) {
            return null;
        }

        $value = (int) str_replace(',', '', $matches[1]);

        return $value > 0 ? $value : null;
    }

    /**
     * @param  array<string, string>  $parameters
     * @return list<string>
     */
    private function parseFormerNames(array $parameters): array
    {
        $raw = null;

        foreach (['former names', 'former_names', 'previous names'] as $key) {
            if (isset($parameters[$key])) {
                $raw = $parameters[$key];

                break;
            }
        }

        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $names = [];

        foreach (preg_split('/\r?\n|<br\s*\/?>/i', $raw) ?: [] as $segment) {
            $segment = trim($segment);

            if ($segment === '') {
                continue;
            }

            if (str_starts_with($segment, '*')) {
                $segment = ltrim($segment, '* ');
            }

            foreach (preg_split('/\s*,\s*/', $this->stripWikiMarkup($segment)) ?: [] as $name) {
                $name = trim($name);

                if ($name !== '') {
                    $names[] = $name;
                }
            }
        }

        return array_values(array_unique($names));
    }

    private function stripWikiMarkup(string $value): string
    {
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*\/>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*?<\/ref>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*/is', '', $value) ?? $value;
        $value = preg_replace('/\{\{[^}]+\}\}/', '', $value) ?? $value;
        $value = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', '$1', $value) ?? $value;
        $value = preg_replace("/'''+/", '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
