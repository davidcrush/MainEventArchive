<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrowsePaginationTest extends TestCase
{
    use RefreshDatabase;

    private ?Promotion $promotion = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_browse_paginates_results_at_default_page_size(): void
    {
        for ($index = 1; $index <= 21; $index++) {
            $this->createPublishedShow([
                'title' => "Show {$index}",
                'slug' => "show-{$index}",
                'date' => sprintf('1997-01-%02d', min($index, 28)),
            ]);
        }

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shows.data', 20)
                ->where('shows.meta.current_page', 1)
                ->where('shows.meta.last_page', 2)
                ->where('shows.meta.total', 21)
                ->where('shows.meta.per_page', 20),
            );
    }

    public function test_browse_second_page_returns_remaining_shows(): void
    {
        for ($index = 1; $index <= 21; $index++) {
            $this->createPublishedShow([
                'title' => "Show {$index}",
                'slug' => "show-{$index}",
                'date' => sprintf('1997-01-%02d', min($index, 28)),
            ]);
        }

        $this->get(route('browse', ['page' => 2]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shows.data', 1)
                ->where('shows.meta.current_page', 2),
            );
    }

    public function test_year_filter_returns_first_page_of_matching_results(): void
    {
        for ($index = 1; $index <= 21; $index++) {
            $this->createPublishedShow([
                'title' => "Show {$index}",
                'slug' => "show-{$index}",
                'date' => sprintf('1997-01-%02d', min($index, 28)),
            ]);
        }

        $this->createPublishedShow([
            'title' => 'Starrcade 1998',
            'slug' => 'starrcade-1998',
            'date' => '1998-12-27',
        ]);

        $this->get(route('browse', ['year' => 1998]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.year', 1998)
                ->where('shows.meta.current_page', 1)
                ->has('shows.data', 1)
                ->where('shows.data.0.slug', 'starrcade-1998'),
            );
    }

    public function test_browse_respects_configured_page_size(): void
    {
        config(['catalog.browse_per_page' => 5]);

        for ($index = 1; $index <= 6; $index++) {
            $this->createPublishedShow([
                'title' => "Show {$index}",
                'slug' => "show-{$index}",
                'date' => sprintf('1997-01-%02d', min($index, 28)),
            ]);
        }

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shows.data', 5)
                ->where('shows.meta.per_page', 5)
                ->where('shows.meta.last_page', 2),
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPublishedShow(array $attributes = []): Show
    {
        $this->promotion ??= Promotion::factory()->wcw()->create();

        return Show::factory()->create(array_merge([
            'promotion_id' => $this->promotion->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'date' => '1997-12-28',
        ], $attributes));
    }
}
