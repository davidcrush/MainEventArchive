<?php

namespace App\Services\Wikipedia;

use App\Data\ParsedWikipediaShowMetadata;
use App\Data\ParsedWikipediaVenueLink;

class WikipediaInfoboxParser
{
    public function parse(string $wikitext): ParsedWikipediaShowMetadata
    {
        $infoboxBody = $this->extractInfoboxBody($wikitext);

        if ($infoboxBody === null) {
            return new ParsedWikipediaShowMetadata(null, null, null, []);
        }

        $parameters = $this->parseInfoboxParameters($infoboxBody);

        $venueRaw = $parameters['venue'] ?? null;
        $venue = $this->parseLocationField($venueRaw);
        $venueLinks = $this->parseVenueLinks($venueRaw);
        $city = $this->parseLocationField($parameters['city'] ?? null);

        if ($city === null && isset($parameters['location'])) {
            $city = $this->parseLocationField($parameters['location']);
        }

        $attendance = $this->parseAttendance($parameters['attendance'] ?? null);

        return new ParsedWikipediaShowMetadata($venue, $city, $attendance, $venueLinks);
    }

    /**
     * @return list<ParsedWikipediaVenueLink>
     */
    public function parseVenueLinks(?string $raw): array
    {
        if ($raw === null || trim($raw) === '') {
            return [];
        }

        $links = [];

        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '*')) {
                $line = ltrim($line, '* ');
            }

            $link = $this->extractWikilink($line);

            if ($link !== null) {
                $links[] = $link;
            }
        }

        return $links;
    }

    private function extractWikilink(string $segment): ?ParsedWikipediaVenueLink
    {
        if (preg_match('/\[\[([^|\]]+)(?:\|([^\]]+))?\]\]/', $segment, $matches) !== 1) {
            return null;
        }

        $pageTitle = trim($matches[1]);
        $displayName = isset($matches[2]) ? trim($matches[2]) : $pageTitle;

        if ($pageTitle === '') {
            return null;
        }

        return new ParsedWikipediaVenueLink($pageTitle, $this->stripWikiMarkup($displayName));
    }

    private function extractInfoboxBody(string $wikitext): ?string
    {
        if (preg_match('/\{\{Infobox\s+wrestling\s+event/i', $wikitext, $match, PREG_OFFSET_CAPTURE) !== 1) {
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
        if (preg_match('/^\{\{Infobox\s+wrestling\s+event/i', $template, $match) !== 1) {
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

    private function parseLocationField(?string $raw): ?string
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $segments = [];

        foreach (preg_split('/\r?\n/', $raw) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '*')) {
                $line = ltrim($line, '* ');
            }

            $cleaned = $this->stripWikiMarkup($line);

            if ($cleaned !== '') {
                $segments[] = $cleaned;
            }
        }

        if ($segments === []) {
            return null;
        }

        if (count($segments) === 1) {
            return $segments[0];
        }

        return implode(', ', $segments);
    }

    private function parseAttendance(?string $raw): ?int
    {
        if ($raw === null || trim($raw) === '') {
            return null;
        }

        $cleaned = $this->stripWikiMarkup($raw);

        if (preg_match('/(\d[\d,]*)/', $cleaned, $matches) !== 1) {
            return null;
        }

        $value = (int) str_replace(',', '', $matches[1]);

        return $value > 0 ? $value : null;
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
