<?php

namespace Database\Factories;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Show>
 */
class ShowFactory extends Factory
{
    protected $model = Show::class;

    public function definition(): array
    {
        $title = fake()->unique()->words(3, true);
        $date = Carbon::parse(fake()->dateTimeBetween('-30 years', 'now'));

        return [
            'promotion_id' => Promotion::factory(),
            'title' => ucwords($title),
            'slug' => app(ShowSlugGenerator::class)->generate(ucwords($title), $date),
            'date' => $date->toDateString(),
            'venue' => fake()->optional()->company(),
            'city' => fake()->optional()->city(),
            'show_type' => ShowType::Ppv,
            'brand' => null,
            'attendance' => fake()->optional()->numberBetween(5000, 50000),
            'status' => ShowStatus::Published,
            'cagematch_url' => null,
            'source' => 'manual',
            'source_id' => null,
            'source_url' => null,
            'imported_at' => null,
            'verified_at' => now(),
            'verified_by' => null,
        ];
    }

    public function pendingReview(): static
    {
        return $this->state(fn (): array => [
            'status' => ShowStatus::PendingReview,
            'verified_at' => null,
            'verified_by' => null,
            'imported_at' => now(),
            'source' => 'wikidata',
        ]);
    }

    public function draft(): static
    {
        return $this->state(fn (): array => [
            'status' => ShowStatus::Draft,
            'verified_at' => null,
            'verified_by' => null,
        ]);
    }

    public function starrcade1997(): static
    {
        return $this->state(function (): array {
            $title = 'Starrcade 1997';
            $date = '1997-12-28';

            return [
                'title' => $title,
                'slug' => app(ShowSlugGenerator::class)->generate($title, Carbon::parse($date)),
                'date' => $date,
                'venue' => 'MCI Center',
                'city' => 'Washington, D.C.',
                'show_type' => ShowType::Ppv,
                'status' => ShowStatus::Published,
            ];
        });
    }
}
