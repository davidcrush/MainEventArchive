<?php

namespace Database\Seeders;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class WcwNitroCatalogSeeder extends Seeder
{
    private const NITRO_TITLE_PREFIX = 'WCW Monday Nitro';

    public function run(): void
    {
        $promotion = Promotion::query()->firstOrCreate(
            ['slug' => 'wcw'],
            ['name' => 'World Championship Wrestling'],
        );

        $events = require database_path('seeders/data/wcw_nitro_1996.php');
        $slugGenerator = app(ShowSlugGenerator::class);

        foreach ($events as $event) {
            $date = $event['date'];
            $title = $event['title'];
            $existing = $this->resolveNitroShowForDate($promotion->id, $date);

            $attributes = [
                'title' => $title,
                'episode_number' => $event['episode_number'],
                'venue' => $event['venue'],
                'city' => $event['city'],
                'tv_rating' => $event['tv_rating'] ?? null,
                'show_type' => ShowType::Tv,
                'source' => 'manual',
                'imported_at' => now(),
            ];

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

    private function resolveNitroShowForDate(int $promotionId, string $date): ?Show
    {
        $shows = Show::query()
            ->where('promotion_id', $promotionId)
            ->where('show_type', ShowType::Tv)
            ->where('title', 'like', self::NITRO_TITLE_PREFIX.'%')
            ->whereDate('date', $date)
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

        foreach (['episode_number', 'venue', 'city', 'tv_rating', 'show_type'] as $field) {
            if (array_key_exists($field, $attributes) && $show->{$field} != $attributes[$field]) {
                $updates[$field] = $attributes[$field];
            }
        }

        if ($show->imported_at === null) {
            $updates['imported_at'] = $attributes['imported_at'];
        }

        if ($updates !== []) {
            $show->update($updates);
        }
    }
}
