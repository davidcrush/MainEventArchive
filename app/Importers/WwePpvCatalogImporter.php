<?php

namespace App\Importers;

use App\Data\CagematchEvent;
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use App\Services\Cagematch\CagematchListingParser;
use App\Services\Cagematch\CagematchSavedPageLoader;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;

/**
 * Seeds WWE PPV show shells from bundled Cagematch listing saves. Shells are
 * created as PendingReview and matched by air date so the importer is
 * idempotent and safe to re-run. Match cards and venues are filled separately
 * via shows:import wikipedia.
 */
class WwePpvCatalogImporter
{
    /**
     * @var list<string>
     */
    private const CAGEMATCH_FILES = [
        'docs/third-party/cagematch/WWE-PPVs-1996-1985.mhtml',
        'docs/third-party/cagematch/WWE-PPVs-2003-1996.html',
        'docs/third-party/cagematch/WWE-PPVs-2010-2003.mhtml',
        'docs/third-party/cagematch/WWE-PPVs-2016-2010.mhtml',
        'docs/third-party/cagematch/WWE-PPVs-2021-2016.mhtml',
        'docs/third-party/cagematch/WWE-PPVs-2026-2021.mhtml',
    ];

    public function __construct(
        private readonly CagematchSavedPageLoader $pageLoader,
        private readonly CagematchListingParser $listingParser,
        private readonly CagematchCatalogTitleNormalizer $titleNormalizer,
        private readonly ShowSlugGenerator $slugGenerator,
    ) {}

    /**
     * @return array{created: int, updated: int, deleted: int, total: int}
     */
    public function import(
        Promotion $promotion,
        int $fromYear,
        int $toYear,
        bool $dryRun = false,
    ): array {
        $events = $this->loadEvents($fromYear, $toYear);

        $created = 0;
        $updated = 0;
        $cagematchDates = [];

        foreach ($events as $event) {
            $date = $event->date->toDateString();
            $cagematchDates[] = $date;
            $title = $this->titleNormalizer->normalize($event->title, $event->date);
            $existing = $this->resolveShowForDate($promotion->id, $date);

            if ($dryRun) {
                $existing === null ? $created++ : $updated++;

                continue;
            }

            if ($existing !== null) {
                if ($this->updateShow($existing, $title, $date)) {
                    $updated++;
                }

                continue;
            }

            Show::query()->create([
                'slug' => $this->slugGenerator->generate($title, Carbon::parse($date)),
                'promotion_id' => $promotion->id,
                'title' => $title,
                'date' => $date,
                'show_type' => ShowType::Ppv,
                'status' => ShowStatus::PendingReview,
                'source' => 'manual',
                'imported_at' => now(),
            ]);
            $created++;
        }

        $deleted = 0;

        if (! $dryRun) {
            $deleted = Show::query()
                ->where('promotion_id', $promotion->id)
                ->where('show_type', ShowType::Ppv)
                ->whereBetween('date', ["{$fromYear}-01-01", "{$toYear}-12-31"])
                ->whereNotIn('date', $cagematchDates)
                ->delete();
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'deleted' => $deleted,
            'total' => count($events),
        ];
    }

    public function ensurePromotion(): Promotion
    {
        return Promotion::query()->firstOrCreate(
            ['slug' => 'wwe'],
            ['name' => config('promotions.wwe.name', 'World Wrestling Entertainment')],
        );
    }

    /**
     * @return list<CagematchEvent>
     */
    private function loadEvents(int $fromYear, int $toYear): array
    {
        /** @var array<string, CagematchEvent> $byDate */
        $byDate = [];

        foreach (self::CAGEMATCH_FILES as $relativePath) {
            $html = $this->pageLoader->load(base_path($relativePath));

            foreach ($this->listingParser->parse($html) as $event) {
                if ($event->date->year < $fromYear || $event->date->year > $toYear) {
                    continue;
                }

                $this->mergeEventByDate($byDate, $event);
            }
        }

        ksort($byDate);

        return array_values($byDate);
    }

    /**
     * @param  array<string, CagematchEvent>  $byDate
     */
    private function mergeEventByDate(array &$byDate, CagematchEvent $event): void
    {
        $date = $event->date->toDateString();

        if (! isset($byDate[$date])) {
            $byDate[$date] = $event;

            return;
        }

        if ($this->shouldReplaceEvent($byDate[$date], $event)) {
            $byDate[$date] = $event;
        }
    }

    private function shouldReplaceEvent(CagematchEvent $existing, CagematchEvent $incoming): bool
    {
        $existingIsNxt = $this->isNxtEvent($existing->title);
        $incomingIsNxt = $this->isNxtEvent($incoming->title);

        if ($existingIsNxt && ! $incomingIsNxt) {
            return true;
        }

        if (! $existingIsNxt && $incomingIsNxt) {
            return false;
        }

        return true;
    }

    private function isNxtEvent(string $title): bool
    {
        return stripos($title, 'NXT') !== false;
    }

    private function resolveShowForDate(int $promotionId, string $date): ?Show
    {
        $shows = Show::query()
            ->where('promotion_id', $promotionId)
            ->whereDate('date', $date)
            ->orderByRaw('CASE WHEN cagematch_url IS NOT NULL THEN 0 ELSE 1 END')
            ->orderByDesc('id')
            ->get();

        if ($shows->isEmpty()) {
            return null;
        }

        $keeper = $shows->first();

        if ($shows->count() > 1) {
            Show::query()
                ->whereIn('id', $shows->skip(1)->pluck('id'))
                ->delete();
        }

        return $keeper;
    }

    private function updateShow(Show $show, string $title, string $date): bool
    {
        $updates = [];

        if ($show->title !== $title) {
            $updates['title'] = $title;
            $updates['slug'] = $this->slugGenerator->generate($title, Carbon::parse($date), $show->id);
        }

        if ($show->date->toDateString() !== $date) {
            $updates['date'] = $date;
        }

        if ($updates === []) {
            return false;
        }

        $show->update($updates);

        return true;
    }
}
