<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrowseWatchableFilterTest extends TestCase
{
    use RefreshDatabase;

    private ?Promotion $promotion = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_show_card_resource_includes_has_video_when_full_show_video_exists(): void
    {
        $showWithVideo = $this->createPublishedShow(['title' => 'Starrcade 1997', 'slug' => 'starrcade-1997']);
        $showWithoutVideo = $this->createPublishedShow(['title' => 'Halloween Havoc 1998', 'slug' => 'halloween-havoc-1998']);

        Video::factory()->create([
            'show_id' => $showWithVideo->id,
            'match_id' => null,
        ]);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->has('shows.data', 2)
                ->where('shows.data', fn ($shows) => collect($shows)->contains(
                    fn ($show) => $show['slug'] === $showWithVideo->slug && $show['has_video'] === true,
                ))
                ->where('shows.data', fn ($shows) => collect($shows)->contains(
                    fn ($show) => $show['slug'] === $showWithoutVideo->slug && $show['has_video'] === false,
                )),
            );
    }

    public function test_browse_without_filter_returns_shows_with_and_without_video(): void
    {
        $showWithVideo = $this->createPublishedShow(['title' => 'Great American Bash 1990', 'slug' => 'great-american-bash-1990']);
        $showWithoutVideo = $this->createPublishedShow(['title' => 'Bash at the Beach 1994', 'slug' => 'bash-at-the-beach-1994']);

        Video::factory()->create([
            'show_id' => $showWithVideo->id,
            'match_id' => null,
        ]);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.watchable', false)
                ->has('shows.data', 2)
                ->where('shows.data', fn ($shows) => collect($shows)->pluck('slug')->sort()->values()->all() === collect([
                    $showWithVideo->slug,
                    $showWithoutVideo->slug,
                ])->sort()->values()->all()),
            );
    }

    public function test_browse_with_watchable_filter_returns_only_shows_with_full_show_video(): void
    {
        $showWithVideo = $this->createPublishedShow(['title' => 'Starrcade 1996', 'slug' => 'starrcade-1996']);
        $showWithoutVideo = $this->createPublishedShow(['title' => 'Uncensored 1996', 'slug' => 'uncensored-1996']);

        Video::factory()->create([
            'show_id' => $showWithVideo->id,
            'match_id' => null,
        ]);

        $this->get(route('browse', ['watchable' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.watchable', true)
                ->has('shows.data', 1)
                ->where('shows.data.0.slug', $showWithVideo->slug)
                ->where('shows.data.0.has_video', true),
            );
    }

    public function test_browse_with_watchable_filter_excludes_shows_without_videos(): void
    {
        $showWithoutVideo = $this->createPublishedShow(['title' => 'Fall Brawl 1999', 'slug' => 'fall-brawl-1999']);

        $this->get(route('browse', ['watchable' => 1]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->has('shows.data', 0)
                ->where('shows.meta.total', 0),
            );

        $this->assertDatabaseHas('shows', ['id' => $showWithoutVideo->id]);
    }

    public function test_browse_with_watchable_filter_includes_wwe_ppv_without_youtube_video(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $wwePpv = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'show_type' => ShowType::Ppv,
            'title' => 'Survivor Series 2001',
            'slug' => 'survivor-series-2001-watchable',
            'date' => '2001-11-18',
        ]);

        $this->get(route('browse', ['watchable' => 1, 'promotion' => 'wwe']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.watchable', true)
                ->has('shows.data', 1)
                ->where('shows.data.0.slug', $wwePpv->slug)
                ->where('shows.data.0.has_video', true),
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
