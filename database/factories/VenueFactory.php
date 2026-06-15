<?php

namespace Database\Factories;

use App\Models\Venue;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Venue>
 */
class VenueFactory extends Factory
{
    protected $model = Venue::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(3, true).' Arena';

        return [
            'name' => $name,
            'slug' => Str::slug($name),
            'city' => fake()->city(),
            'state_province' => fake()->state(),
            'country' => 'United States',
            'capacity' => fake()->numberBetween(5000, 20000),
            'wikipedia_page_title' => $name,
            'wikipedia_url' => 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $name),
            'imported_at' => now(),
        ];
    }
}
