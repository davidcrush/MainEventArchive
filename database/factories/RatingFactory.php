<?php

namespace Database\Factories;

use App\Models\Rating;
use App\Models\Show;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Rating>
 */
class RatingFactory extends Factory
{
    protected $model = Rating::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'rateable_type' => Show::class,
            'rateable_id' => Show::factory(),
            'stars' => fake()->numberBetween(1, 5),
        ];
    }
}
