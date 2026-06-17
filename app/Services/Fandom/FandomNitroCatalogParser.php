<?php

namespace App\Services\Fandom;

use Carbon\Carbon;

/**
 * Parses the prowrestling.fandom.com "Template:WCW Nitro results" navbox into an
 * ordered list of every Monday Nitro episode. The navbox lists episodes
 * chronologically (1995 → 2001), so the global episode number is simply the
 * 1-based position — matching the WCW convention where the Sep 4, 1995 premiere
 * is #1.
 *
 * @phpstan-type ParsedNitroEpisode array{pageTitle: string, date: string, episodeNumber: int}
 */
class FandomNitroCatalogParser
{
    /**
     * @return list<ParsedNitroEpisode>
     */
    public function parse(string $wikitext): array
    {
        preg_match_all(
            '/\[\[([A-Z][a-z]+ \d{1,2}, \d{4} Monday Nitro results)(?:\|[^\]]*)?\]\]/',
            $wikitext,
            $matches,
        );

        $episodes = [];
        $seen = [];
        $episodeNumber = 0;

        foreach ($matches[1] as $pageTitle) {
            if (isset($seen[$pageTitle])) {
                continue;
            }

            $date = $this->dateFromPageTitle($pageTitle);

            if ($date === null) {
                continue;
            }

            $seen[$pageTitle] = true;
            $episodeNumber++;

            $episodes[] = [
                'pageTitle' => $pageTitle,
                'date' => $date,
                'episodeNumber' => $episodeNumber,
            ];
        }

        return $episodes;
    }

    private function dateFromPageTitle(string $pageTitle): ?string
    {
        $datePart = trim(str_replace('Monday Nitro results', '', $pageTitle));

        try {
            return Carbon::createFromFormat('F j, Y', $datePart)->toDateString();
        } catch (\Throwable) {
            return null;
        }
    }
}
