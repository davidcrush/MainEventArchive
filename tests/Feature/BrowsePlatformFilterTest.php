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

class BrowsePlatformFilterTest extends TestCase
{
    use RefreshDatabase;

    private ?Promotion $promotion = null;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_browse_without_platform_filter_returns_all_shows(): void
    {
        $youtubeShow = $this->createPublishedShow(['title' => 'Starrcade 1997', 'slug' => 'starrcade-1997']);
        $netflixShow = $this->createPublishedShow(['title' => 'Survivor Series 2001', 'slug' => 'survivor-series-2001']);
        $noVideoShow = $this->createPublishedShow(['title' => 'Halloween Havoc 1998', 'slug' => 'halloween-havoc-1998']);

        Video::factory()->create([
            'show_id' => $youtubeShow->id,
            'match_id' => null,
            'provider' => 'youtube',
        ]);

        Video::factory()->create([
            'show_id' => $netflixShow->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '81930166',
            'url' => 'https://www.netflix.com/watch/81930166',
        ]);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.platform', null)
                ->has('shows', 3),
            );
    }

    public function test_browse_with_youtube_platform_filter_returns_only_youtube_shows(): void
    {
        $youtubeShow = $this->createPublishedShow(['title' => 'Starrcade 1996', 'slug' => 'starrcade-1996']);
        $netflixShow = $this->createPublishedShow(['title' => 'Royal Rumble 2001', 'slug' => 'royal-rumble-2001']);

        Video::factory()->create([
            'show_id' => $youtubeShow->id,
            'match_id' => null,
            'provider' => 'youtube',
        ]);

        Video::factory()->create([
            'show_id' => $netflixShow->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '81929681',
            'url' => 'https://www.netflix.com/watch/81929681',
        ]);

        $this->get(route('browse', ['platform' => 'youtube']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.platform', 'youtube')
                ->has('shows', 1)
                ->where('shows.0.slug', $youtubeShow->slug),
            );
    }

    public function test_browse_with_netflix_platform_filter_returns_only_netflix_shows(): void
    {
        $youtubeShow = $this->createPublishedShow(['title' => 'Greed 2001', 'slug' => 'greed-2001']);
        $netflixShow = $this->createPublishedShow(['title' => 'Vengeance 2001', 'slug' => 'vengeance-2001']);

        Video::factory()->create([
            'show_id' => $youtubeShow->id,
            'match_id' => null,
            'provider' => 'youtube',
        ]);

        Video::factory()->create([
            'show_id' => $netflixShow->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '81930237',
            'url' => 'https://www.netflix.com/watch/81930237',
        ]);

        $this->get(route('browse', ['platform' => 'netflix']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Browse/Index')
                ->where('filters.platform', 'netflix')
                ->has('shows', 1)
                ->where('shows.0.slug', $netflixShow->slug),
            );
    }

    public function test_dual_source_show_appears_in_both_platform_filters(): void
    {
        $dualSourceShow = $this->createPublishedShow(['title' => 'Vengeance 2001 Dual', 'slug' => 'vengeance-2001-dual']);

        Video::factory()->create([
            'show_id' => $dualSourceShow->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'abc12345678',
            'url' => 'https://www.youtube.com/watch?v=abc12345678',
        ]);

        Video::factory()->create([
            'show_id' => $dualSourceShow->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '81930237',
            'url' => 'https://www.netflix.com/watch/81930237',
        ]);

        $this->get(route('browse', ['platform' => 'youtube']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shows', 1)
                ->where('shows.0.slug', $dualSourceShow->slug),
            );

        $this->get(route('browse', ['platform' => 'netflix']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->has('shows', 1)
                ->where('shows.0.slug', $dualSourceShow->slug),
            );
    }

    public function test_platform_filter_composes_with_year_filter(): void
    {
        $youtube1997 = $this->createPublishedShow([
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997-youtube',
            'date' => '1997-12-28',
        ]);
        $youtube1998 = $this->createPublishedShow([
            'title' => 'Starrcade 1998',
            'slug' => 'starrcade-1998-youtube',
            'date' => '1998-12-27',
        ]);

        Video::factory()->create(['show_id' => $youtube1997->id, 'match_id' => null, 'provider' => 'youtube']);
        Video::factory()->create(['show_id' => $youtube1998->id, 'match_id' => null, 'provider' => 'youtube']);

        $this->get(route('browse', ['platform' => 'youtube', 'year' => 1997]))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.platform', 'youtube')
                ->where('filters.year', 1997)
                ->has('shows', 1)
                ->where('shows.0.slug', $youtube1997->slug),
            );
    }

    public function test_invalid_platform_filter_is_treated_as_all(): void
    {
        $youtubeShow = $this->createPublishedShow(['title' => 'Starrcade 1995', 'slug' => 'starrcade-1995']);

        Video::factory()->create([
            'show_id' => $youtubeShow->id,
            'match_id' => null,
            'provider' => 'youtube',
        ]);

        $this->get(route('browse', ['platform' => 'vimeo']))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('filters.platform', null)
                ->has('shows', 1)
                ->where('shows.0.slug', $youtubeShow->slug),
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
