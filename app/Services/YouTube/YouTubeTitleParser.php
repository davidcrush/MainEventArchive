<?php

namespace App\Services\YouTube;

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

        if (preg_match('/^FULL EVENT:\s*(WCW\s+)?/i', $title) === 1) {
            $title = preg_replace('/^FULL EVENT:\s*(WCW\s+)?/i', '', $title) ?? $title;
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

    public function isFullEventTitle(string $youtubeTitle): bool
    {
        return preg_match('/^FULL EVENT:\s*/i', trim($youtubeTitle)) === 1;
    }
}
