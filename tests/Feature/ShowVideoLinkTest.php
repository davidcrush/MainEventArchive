<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowVideoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_includes_youtube_watch_target(): void
    {
        $show = $this->createPublishedShow();

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'ftPK-rYz7Vc',
            'url' => 'https://www.youtube.com/watch?v=ftPK-rYz7Vc',
            'is_primary' => true,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.watch_targets.0.provider', 'youtube')
                ->where('show.watch_targets.0.url', 'https://www.youtube.com/watch?v=ftPK-rYz7Vc'),
            );
    }

    public function test_show_page_includes_youtube_and_netflix_watch_targets_when_both_exist(): void
    {
        config(['streaming.netflix.wwe_ppv_search_enabled' => true]);

        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'title' => 'Vengeance 2001',
            'slug' => 'vengeance-2001-test',
            'date' => '2001-12-09',
            'show_type' => ShowType::Ppv,
        ]);

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'abc12345678',
            'url' => 'https://www.youtube.com/watch?v=abc12345678',
            'is_primary' => true,
        ]);

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '81930237',
            'url' => 'https://www.netflix.com/watch/81930237',
            'is_primary' => true,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->has('show.watch_targets', 2)
                ->where('show.watch_targets.0.provider', 'youtube')
                ->where('show.watch_targets.0.url', 'https://www.youtube.com/watch?v=abc12345678')
                ->where('show.watch_targets.1.provider', 'netflix')
                ->where('show.watch_targets.1.mode', 'deep_link')
                ->where('show.watch_targets.1.url', 'https://www.netflix.com/watch/81930237'),
            );
    }

    public function test_wwe_ppv_without_videos_includes_netflix_search_target(): void
    {
        config(['streaming.netflix.wwe_ppv_search_enabled' => true]);

        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'title' => 'Survivor Series 2001',
            'slug' => 'survivor-series-2001-test',
            'date' => '2001-11-18',
            'show_type' => ShowType::Ppv,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.watch_targets.0.provider', 'netflix')
                ->where('show.watch_targets.0.mode', 'search')
                ->where(
                    'show.watch_targets.0.url',
                    'https://www.netflix.com/search?q=Survivor%20Series%202001',
                ),
            );
    }

    public function test_show_page_without_videos_has_empty_watch_targets(): void
    {
        $show = $this->createPublishedShow();

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.watch_targets', []),
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPublishedShow(array $attributes = []): Show
    {
        $promotion = Promotion::factory()->wcw()->create();

        return Show::factory()->create(array_merge([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997-'.fake()->unique()->numerify('###'),
            'date' => '1997-12-28',
        ], $attributes));
    }
}
