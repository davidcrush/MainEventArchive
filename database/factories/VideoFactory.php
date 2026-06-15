<?php

namespace Database\Factories;

use App\Models\Show;
use App\Models\Video;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Video>
 */
class VideoFactory extends Factory
{
    protected $model = Video::class;

    public function definition(): array
    {
        return [
            'show_id' => Show::factory(),
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => fake()->regexify('[A-Za-z0-9_-]{11}'),
            'url' => 'https://www.youtube.com/watch?v='.fake()->regexify('[A-Za-z0-9_-]{11}'),
            'title' => fake()->optional()->sentence(3),
            'duration_seconds' => fake()->optional()->numberBetween(3600, 10800),
            'embeddable' => true,
            'embed_disabled_reason' => null,
            'last_verified_at' => null,
            'is_primary' => true,
        ];
    }
}
