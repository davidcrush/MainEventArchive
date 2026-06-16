<?php

namespace Database\Seeders;

use App\Models\Promotion;
use Illuminate\Database\Seeder;

class PromotionProfileSeeder extends Seeder
{
    /**
     * Upsert promotion rows and curated profile fields from config/promotions.php.
     */
    public function run(): void
    {
        foreach (config('promotions', []) as $slug => $profile) {
            $promotion = Promotion::query()->firstOrCreate(
                ['slug' => $slug],
                ['name' => $profile['name']],
            );

            $promotion->update([
                'name' => $profile['name'],
                'logo_path' => $profile['logo_path'] ?? null,
                'founded_year' => $profile['founded_year'] ?? null,
                'active_from_year' => $profile['active_from_year'] ?? null,
                'active_to_year' => $profile['active_to_year'] ?? null,
                'is_active' => $profile['is_active'] ?? false,
                'headquarters' => $profile['headquarters'] ?? null,
                'description' => $profile['description'] ?? null,
                'wikipedia_url' => $profile['wikipedia_url'] ?? null,
                'sort_order' => $profile['sort_order'] ?? 0,
            ]);
        }
    }
}
