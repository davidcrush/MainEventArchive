<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrowseAllPromotionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_browse_defaults_to_wcw_promotion_filter(): void
    {
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $wcw->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997',
        ]);

        Show::factory()->create([
            'promotion_id' => $wwe->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'title' => 'WrestleMania 2001',
            'slug' => 'wrestlemania-2001',
        ]);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.promotion', 'wcw')
                ->has('shows.data', 1)
                ->where('shows.data.0.slug', 'starrcade-1997'),
            );
    }

    public function test_browse_with_all_promotions_returns_shows_from_multiple_promotions(): void
    {
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $wcw->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997',
            'date' => '1997-12-28',
        ]);

        Show::factory()->create([
            'promotion_id' => $wwe->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'title' => 'WrestleMania 2001',
            'slug' => 'wrestlemania-2001',
            'date' => '2001-04-01',
        ]);

        $this->get(route('browse', ['promotion' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.promotion', 'all')
                ->has('shows.data', 2)
                ->where('shows.data', fn ($shows) => collect($shows)->pluck('slug')->sort()->values()->all() === [
                    'starrcade-1997',
                    'wrestlemania-2001',
                ]),
            );
    }

    public function test_years_include_all_promotions_when_promotion_filter_is_all(): void
    {
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $wcw->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'date' => '1997-12-28',
        ]);

        Show::factory()->create([
            'promotion_id' => $wwe->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'date' => '2001-04-01',
        ]);

        $this->get(route('browse', ['promotion' => 'all']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('years', fn ($years) => collect($years)->contains(1997) && collect($years)->contains(2001)),
            );
    }
}
