<?php

namespace Database\Seeders;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WcwClashCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $promotion = Promotion::query()->firstOrCreate(
            ['slug' => 'wcw'],
            ['name' => 'World Championship Wrestling'],
        );

        $events = require database_path('seeders/data/wcw_clash_catalog.php');
        $slugGenerator = app(ShowSlugGenerator::class);

        foreach ($events as $event) {
            $date = $event['date'];
            $title = $event['title'];
            $existing = $this->resolveShowForDate($promotion->id, $date);

            $attributes = [
                'title' => $title,
                'show_type' => ShowType::Tv,
                'source' => 'manual',
                'imported_at' => now(),
            ];

            if (filled($event['cagematch_event_id'] ?? null)) {
                $attributes['cagematch_url'] = sprintf(
                    config('cagematch.event_url_template'),
                    $event['cagematch_event_id'],
                );
            }

            if ($existing !== null) {
                $this->updateShow($existing, $title, $date, $slugGenerator, $attributes);

                continue;
            }

            Show::query()->create(array_merge($attributes, [
                'slug' => $slugGenerator->generate($title, Carbon::parse($date)),
                'promotion_id' => $promotion->id,
                'date' => $date,
                'status' => ShowStatus::PendingReview,
            ]));
        }
    }

    private function resolveShowForDate(int $promotionId, string $date): ?Show
    {
        $shows = Show::query()
            ->where('promotion_id', $promotionId)
            ->where('show_type', ShowType::Tv)
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

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateShow(
        Show $show,
        string $title,
        string $date,
        ShowSlugGenerator $slugGenerator,
        array $attributes,
    ): void {
        $updates = [];

        if ($show->title !== $title) {
            $updates['title'] = $title;
            $updates['slug'] = $slugGenerator->generate($title, Carbon::parse($date), $show->id);
        }

        if ($show->date->toDateString() !== $date) {
            $updates['date'] = $date;
        }

        if ($show->show_type !== ShowType::Tv) {
            $updates['show_type'] = ShowType::Tv;
        }

        if (isset($attributes['cagematch_url']) && $show->cagematch_url !== $attributes['cagematch_url']) {
            $updates['cagematch_url'] = $attributes['cagematch_url'];
        }

        if ($show->imported_at === null) {
            $updates['imported_at'] = $attributes['imported_at'];
        }

        if ($updates !== []) {
            $show->update($updates);
        }
    }
}
