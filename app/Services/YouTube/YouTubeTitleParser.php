<?php

namespace App\Services\YouTube;

use Carbon\CarbonInterface;
use Carbon\Exceptions\InvalidFormatException;
use Illuminate\Support\Carbon;

class YouTubeTitleParser
{
    /**
     * @return array{eventTitle: string, year: ?int}|null
     */
    public function parse(string $youtubeTitle): ?array
    {
        $title = html_entity_decode(trim($youtubeTitle), ENT_QUOTES | ENT_HTML5);

        if ($title === '') {
            return null;
        }

        if (preg_match('/^FULL EVENT:\s*(?:(?:WCW|WWE|WWF)\s+)?/i', $title) === 1) {
            $title = preg_replace('/^FULL EVENT:\s*(?:(?:WCW|WWE|WWF)\s+)?/i', '', $title) ?? $title;
        }

        $title = preg_replace('/^(?:WWE|WWF)\s+/i', '', $title) ?? $title;

        if ($this->parseInYourHouse($title) !== null) {
            $year = null;

            if (preg_match('/\b((?:19|20)\d{2})\b/', $title, $matches) === 1) {
                $year = (int) $matches[1];
            }

            return [
                'eventTitle' => $title,
                'year' => $year,
            ];
        }

        $segments = preg_split('/\s+\|\s+| – | - /u', $title, 2);

        if ($segments === false) {
            return null;
        }

        $eventTitle = trim($segments[0]);

        if ($eventTitle === '') {
            return null;
        }

        $year = null;

        if (preg_match('/\b((?:19|20)\d{2})\b/', $eventTitle, $matches) === 1) {
            $year = (int) $matches[1];
        }

        return [
            'eventTitle' => $eventTitle,
            'year' => $year,
        ];
    }

    /**
     * @return array{number: ?int, subtitle: ?string}|null
     */
    public function parseInYourHouse(string $title): ?array
    {
        $title = html_entity_decode(trim($title), ENT_QUOTES | ENT_HTML5);
        $title = preg_replace('/^(?:WWE|WWF)\s+/i', '', $title) ?? $title;

        if (preg_match('/^In Your House\s*#\s*(\d+)\b/i', $title, $matches) === 1) {
            return [
                'number' => (int) $matches[1],
                'subtitle' => null,
            ];
        }

        if (preg_match('/^In Your House\s*(?:-\s*|:)\s*(.+)$/i', $title, $matches) === 1) {
            return [
                'number' => null,
                'subtitle' => trim($matches[1]),
            ];
        }

        return null;
    }

    public function isFullEventTitle(string $youtubeTitle): bool
    {
        return preg_match('/^FULL EVENT:\s*/i', trim($youtubeTitle)) === 1;
    }

    public function isFullEpisodeTitle(string $youtubeTitle): bool
    {
        return preg_match('/^FULL EPISODE:\s*/i', trim($youtubeTitle)) === 1;
    }

    public function isSyncableTitle(string $youtubeTitle, bool $includeFullEpisodes = false): bool
    {
        if ($this->isFullEventTitle($youtubeTitle)) {
            return true;
        }

        return $includeFullEpisodes && $this->isFullEpisodeTitle($youtubeTitle);
    }

    public function parseNitroAirDate(string $youtubeTitle): ?CarbonInterface
    {
        if (! $this->isFullEpisodeTitle($youtubeTitle)) {
            return null;
        }

        $title = html_entity_decode(trim($youtubeTitle), ENT_QUOTES | ENT_HTML5);

        if (preg_match('/WCW Monday Nitro,\s*([A-Za-z]+\.?\s+\d{1,2},\s+(?:19|20)\d{2})/i', $title, $matches) !== 1) {
            return null;
        }

        try {
            return Carbon::parse(trim($matches[1]));
        } catch (InvalidFormatException) {
            return null;
        }
    }
}
