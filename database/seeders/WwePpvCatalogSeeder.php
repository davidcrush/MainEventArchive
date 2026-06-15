<?php

namespace Database\Seeders;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use App\Services\Cagematch\CagematchListingParser;
use App\Services\Cagematch\CagematchSavedPageLoader;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WwePpvCatalogSeeder extends Seeder
{
    private const CAGEMATCH_HTML = 'docs/third-party/cagematch/WWE-PPVs-2003-1996.html';

    private const FROM_YEAR = 1996;

    private const TO_YEAR = 2001;

    public function run(): void
    {
        $promotion = Promotion::query()->firstOrCreate(
            ['slug' => 'wwe'],
            ['name' => config('promotions.wwe.name', 'World Wrestling Entertainment')],
        );

        $html = app(CagematchSavedPageLoader::class)->load(base_path(self::CAGEMATCH_HTML));
        $events = app(CagematchListingParser::class)->parse($html);
        $normalizer = app(CagematchCatalogTitleNormalizer::class);
        $slugGenerator = app(ShowSlugGenerator::class);

        $cagematchDates = [];

        foreach ($events as $event) {
            if ($event->date->year < self::FROM_YEAR || $event->date->year > self::TO_YEAR) {
                continue;
            }

            $date = $event->date->toDateString();
            $cagematchDates[] = $date;
            $title = $normalizer->normalize($event->title, $event->date);

            $existing = $this->resolveShowForDate($promotion->id, $date);

            if ($existing !== null) {
                $this->updateShow($existing, $title, $date, $slugGenerator);

                continue;
            }

            Show::query()->create([
                'slug' => $slugGenerator->generate($title, Carbon::parse($date)),
                'promotion_id' => $promotion->id,
                'title' => $title,
                'date' => $date,
                'show_type' => ShowType::Ppv,
                'status' => ShowStatus::PendingReview,
                'source' => 'manual',
                'imported_at' => now(),
            ]);
        }

        Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->whereBetween('date', [self::FROM_YEAR.'-01-01', self::TO_YEAR.'-12-31'])
            ->whereNotIn('date', $cagematchDates)
            ->delete();
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

    private function updateShow(Show $show, string $title, string $date, ShowSlugGenerator $slugGenerator): void
    {
        $updates = [];

        if ($show->title !== $title) {
            $updates['title'] = $title;
            $updates['slug'] = $slugGenerator->generate($title, Carbon::parse($date), $show->id);
        }

        if ($show->date->toDateString() !== $date) {
            $updates['date'] = $date;
        }

        if ($updates !== []) {
            $show->update($updates);
        }
    }
}
