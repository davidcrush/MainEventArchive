<?php

namespace Database\Factories;

use App\Models\Show;
use App\Models\User;
use App\Models\WatchedShow;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<WatchedShow>
 */
class WatchedShowFactory extends Factory
{
    protected $model = WatchedShow::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'show_id' => Show::factory(),
            'watched_at' => now(),
        ];
    }
}
