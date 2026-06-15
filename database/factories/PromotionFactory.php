<?php

namespace Database\Factories;

use App\Models\Promotion;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Promotion>
 */
class PromotionFactory extends Factory
{
    protected $model = Promotion::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name),
        ];
    }

    public function wcw(): static
    {
        return $this->state(fn (): array => [
            'name' => 'World Championship Wrestling',
            'slug' => 'wcw',
        ]);
    }

    public function wwe(): static
    {
        return $this->state(fn (): array => [
            'name' => 'World Wrestling Entertainment',
            'slug' => 'wwe',
        ]);
    }
}
