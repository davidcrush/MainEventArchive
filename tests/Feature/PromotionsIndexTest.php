<?php

namespace Tests\Feature;

use App\Models\Promotion;
use Database\Seeders\PromotionProfileSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

class PromotionsIndexTest extends TestCase
{
    use RefreshDatabase;

    public function test_promotions_index_renders_listed_promotions_in_sort_order(): void
    {
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();

        $this->seedProfile($wwe, config('promotions.wwe'));
        $this->seedProfile($wcw, config('promotions.wcw'));

        $this->get(route('promotions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Promotions/Index')
                ->has('promotions', 2)
                ->where('promotions.0.slug', 'wwe')
                ->where('promotions.0.name', 'World Wrestling Entertainment')
                ->where('promotions.0.status_label', 'Active')
                ->where('promotions.0.active_years_label', '1952–present')
                ->where('promotions.0.headquarters', 'Stamford, Connecticut, U.S.')
                ->where('promotions.1.slug', 'wcw')
                ->where('promotions.1.status_label', 'Defunct')
                ->where('promotions.1.active_years_label', '1988–2001')
            );
    }

    public function test_promotions_without_description_are_excluded(): void
    {
        Promotion::factory()->wcw()->create(['description' => null]);
        Promotion::factory()->wwe()->create(['description' => 'Listed promotion.']);

        $this->get(route('promotions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Promotions/Index')
                ->has('promotions', 1)
                ->where('promotions.0.slug', 'wwe')
            );
    }

    public function test_promotion_profile_seeder_creates_missing_promotions(): void
    {
        Promotion::factory()->wcw()->create();
        Promotion::factory()->wwe()->create();

        $this->seed(PromotionProfileSeeder::class);

        $this->assertDatabaseCount('promotions', 5);

        $this->get(route('promotions.index'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->component('Promotions/Index')
                ->has('promotions', 5)
                ->where('promotions.0.slug', 'wwe')
                ->where('promotions.1.slug', 'aew')
                ->where('promotions.2.slug', 'tna')
                ->where('promotions.3.slug', 'wcw')
                ->where('promotions.4.slug', 'ecw')
            );
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function seedProfile(Promotion $promotion, array $profile): void
    {
        $promotion->update([
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
