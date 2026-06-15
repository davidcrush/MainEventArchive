<?php

namespace App\Services\Wikipedia;

use App\Models\Promotion;
use App\Models\Show;

class WikipediaNitroMetadataImporter
{
    private const NITRO_PAGE_TITLE = 'WCW Monday Nitro';

    private const NITRO_TITLE_PREFIX = 'WCW Monday Nitro';

    public function __construct(
        private readonly WikipediaClient $client,
        private readonly WikipediaNitroNotableEpisodesParser $parser,
    ) {}

    /**
     * @return array{updated: int, skipped: int, warnings: list<string>}
     */
    public function import(Promotion $promotion): array
    {
        $wikitext = $this->client->fetchWikitext(self::NITRO_PAGE_TITLE);
        $episodes = $this->parser->parse($wikitext);
        $sourceUrl = 'https://en.wikipedia.org/wiki/WCW_Monday_Nitro';

        $updated = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($episodes as $episode) {
            $show = Show::query()
                ->where('promotion_id', $promotion->id)
                ->where('title', 'like', self::NITRO_TITLE_PREFIX.'%')
                ->whereDate('date', $episode->date->toDateString())
                ->first();

            if ($show === null) {
                $warnings[] = "No Nitro catalog show found for {$episode->date->toDateString()} ({$episode->episodeTitle}).";
                $skipped++;

                continue;
            }

            $updates = [
                'source_url' => $sourceUrl,
                'imported_at' => now(),
            ];

            if ($episode->tvRating !== null) {
                $updates['tv_rating'] = $episode->tvRating;
            }

            if ($show->venue === null && $episode->venue !== null) {
                $updates['venue'] = $episode->venue;
            }

            if ($show->city === null && $episode->city !== null) {
                $updates['city'] = $episode->city;
            }

            if ($show->source === 'manual') {
                $updates['source'] = 'wikipedia';
            }

            $show->update($updates);
            $updated++;
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'warnings' => $warnings,
        ];
    }
}
