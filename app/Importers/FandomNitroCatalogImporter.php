<?php

namespace App\Importers;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Fandom\FandomClient;
use App\Services\Fandom\FandomNitroCatalogParser;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;

/**
 * Seeds WCW Monday Nitro show shells (one per episode) by enumerating the
 * prowrestling.fandom.com "Template:WCW Nitro results" navbox. Shells are
 * created as PendingReview and matched by air date so the importer is
 * idempotent and safe to re-run. Match cards and venue/city are filled in
 * separately by {@see FandomNitroImporter} via shows:import-nitro-cards.
 */
class FandomNitroCatalogImporter
{
    private const NAVBOX_TITLE = 'Template:WCW Nitro results';

    private const NITRO_TITLE_PREFIX = 'WCW Monday Nitro';

    public function __construct(
        private readonly FandomClient $client,
        private readonly FandomNitroCatalogParser $parser,
        private readonly ShowSlugGenerator $slugGenerator,
    ) {}

    /**
     * @return array{created: int, updated: int, total: int}
     */
    public function import(
        Promotion $promotion,
        ?int $fromYear = null,
        ?int $toYear = null,
        bool $dryRun = false,
    ): array {
        $wikitext = $this->client->fetchWikitext(self::NAVBOX_TITLE);
        $episodes = $this->parser->parse($wikitext);

        $created = 0;
        $updated = 0;
        $total = 0;

        foreach ($episodes as $episode) {
            $year = (int) substr($episode['date'], 0, 4);

            if ($fromYear !== null && $year < $fromYear) {
                continue;
            }

            if ($toYear !== null && $year > $toYear) {
                continue;
            }

            $total++;
            $title = self::NITRO_TITLE_PREFIX." #{$episode['episodeNumber']}";
            $existing = $this->resolveNitroShowForDate($promotion->id, $episode['date']);

            if ($dryRun) {
                $existing === null ? $created++ : $updated++;

                continue;
            }

            if ($existing !== null) {
                $this->updateShow($existing, $title, $episode);
                $updated++;

                continue;
            }

            Show::query()->create([
                'promotion_id' => $promotion->id,
                'title' => $title,
                'slug' => $this->slugGenerator->generate($title, Carbon::parse($episode['date'])),
                'date' => $episode['date'],
                'episode_number' => $episode['episodeNumber'],
                'show_type' => ShowType::Tv,
                'status' => ShowStatus::PendingReview,
                'source' => 'fandom',
                'imported_at' => now(),
            ]);

            $created++;
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total' => $total,
        ];
    }

    private function resolveNitroShowForDate(int $promotionId, string $date): ?Show
    {
        return Show::query()
            ->where('promotion_id', $promotionId)
            ->where('show_type', ShowType::Tv)
            ->where('title', 'like', self::NITRO_TITLE_PREFIX.'%')
            ->whereDate('date', $date)
            ->orderByDesc('id')
            ->first();
    }

    /**
     * @param  array{pageTitle: string, date: string, episodeNumber: int}  $episode
     */
    private function updateShow(Show $show, string $title, array $episode): void
    {
        $updates = [];

        if ($show->title !== $title) {
            $updates['title'] = $title;
            $updates['slug'] = $this->slugGenerator->generate($title, Carbon::parse($episode['date']), $show->id);
        }

        if ($show->episode_number !== $episode['episodeNumber']) {
            $updates['episode_number'] = $episode['episodeNumber'];
        }

        if ($show->imported_at === null) {
            $updates['imported_at'] = now();
        }

        if ($updates !== []) {
            $show->update($updates);
        }
    }
}
