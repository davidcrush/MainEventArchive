<?php

namespace App\Services\Wikipedia;

use App\Data\ParsedWikipediaNitroNotableEpisode;
use Carbon\Carbon;
use RuntimeException;

class WikipediaNitroNotableEpisodesParser
{
    /**
     * @return list<ParsedWikipediaNitroNotableEpisode>
     */
    public function parse(string $wikitext): array
    {
        $section = $this->extractNotableEpisodesSection($wikitext);

        if ($section === null) {
            throw new RuntimeException('No Notable episodes section found on Wikipedia page.');
        }

        if (preg_match('/\{\|.*?\|\}/is', $section, $tableMatch) !== 1) {
            throw new RuntimeException('Notable episodes section does not contain a wikitable.');
        }

        $rows = preg_split('/\n\|-/', $tableMatch[0]) ?: [];
        $episodes = [];

        foreach ($rows as $row) {
            if (! str_contains($row, '|') || str_contains(strtolower($row), '! episode title')) {
                continue;
            }

            $cells = $this->extractTableCells($row);

            if (count($cells) < 4) {
                continue;
            }

            $episodeTitle = $this->stripWikiMarkup($cells[0]);
            $dateRaw = $this->stripWikiMarkup($cells[1]);
            $venue = $this->nullableCell($cells[2] ?? null);
            $city = $this->nullableCell($cells[3] ?? null);
            $ratingRaw = $this->stripWikiMarkup($cells[4] ?? '');
            $note = isset($cells[5]) ? $this->nullableCell($cells[5]) : null;

            if ($episodeTitle === '' || $dateRaw === '') {
                continue;
            }

            try {
                $date = Carbon::parse($dateRaw);
            } catch (\Throwable) {
                continue;
            }

            $episodes[] = new ParsedWikipediaNitroNotableEpisode(
                episodeTitle: $episodeTitle,
                date: $date,
                venue: $venue,
                city: $city,
                tvRating: $this->parseTvRating($ratingRaw),
                note: $note,
            );
        }

        if ($episodes === []) {
            throw new RuntimeException('Notable episodes wikitable contained no parseable rows.');
        }

        return $episodes;
    }

    /**
     * @return list<string>
     */
    private function extractTableCells(string $row): array
    {
        if (str_contains($row, '||')) {
            $cells = preg_split('/\s*\|\|\s*/', $row) ?: [];
        } else {
            $cells = preg_split('/\n\|/', $row) ?: [];
        }

        return array_values(array_filter(array_map(
            function (string $cell): string {
                $cell = preg_replace('/^\|+\s*/', '', trim($cell)) ?? trim($cell);

                return trim($cell);
            },
            $cells,
        ), static fn (string $cell): bool => $cell !== ''));
    }

    private function extractNotableEpisodesSection(string $wikitext): ?string
    {
        if (preg_match('/==\s*Notable episodes\s*==(.+?)(?=\n==[^=]|$)/is', $wikitext, $matches) !== 1) {
            return null;
        }

        return $matches[1];
    }

    private function parseTvRating(string $raw): ?float
    {
        if ($raw === '' || ! is_numeric($raw)) {
            return null;
        }

        return (float) $raw;
    }

    private function nullableCell(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $value = $this->stripWikiMarkup($raw);

        return $value === '' ? null : $value;
    }

    private function stripWikiMarkup(string $value): string
    {
        $value = preg_replace('/<!--.*?-->/s', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*\/>/is', '', $value) ?? $value;
        $value = preg_replace('/<ref[^>]*>.*?<\/ref>/is', '', $value) ?? $value;
        $value = preg_replace('/\{\{[^}]+\}\}/', '', $value) ?? $value;
        $value = preg_replace('/\[\[(?:[^|\]]+\|)?([^\]]+)\]\]/', '$1', $value) ?? $value;
        $value = preg_replace("/'''+/", '', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
