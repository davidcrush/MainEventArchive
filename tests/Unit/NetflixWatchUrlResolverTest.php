<?php

namespace Tests\Unit;

use App\Data\WatchTarget;
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use App\Services\Streaming\NetflixWatchUrlResolver;
use App\Services\Streaming\WatchTargetResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NetflixWatchUrlResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_search_url_for_wwe_ppv_without_netflix_video(): void
    {
        config(['streaming.netflix.wwe_ppv_search_enabled' => true]);

        $show = $this->createWwePpv('Survivor Series 2001');

        $target = app(NetflixWatchUrlResolver::class)->resolve($show);

        $this->assertInstanceOf(WatchTarget::class, $target);
        $this->assertSame('netflix', $target->provider);
        $this->assertSame('search', $target->mode);
        $this->assertSame(
            'https://www.netflix.com/search?q=Survivor%20Series%202001',
            $target->url,
        );
    }

    public function test_returns_deep_link_when_netflix_video_exists(): void
    {
        $show = $this->createWwePpv('Survivor Series 2001');

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'netflix',
            'external_id' => '80117477',
            'url' => 'https://www.netflix.com/watch/80117477',
            'is_primary' => true,
        ]);

        $target = app(NetflixWatchUrlResolver::class)->resolve($show->fresh());

        $this->assertSame('deep_link', $target->mode);
        $this->assertSame('https://www.netflix.com/watch/80117477', $target->url);
    }

    public function test_returns_null_for_wcw_ppv_without_netflix_video(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $this->assertNull(app(NetflixWatchUrlResolver::class)->resolve($show));
    }

    public function test_returns_null_when_search_fallback_disabled(): void
    {
        config(['streaming.netflix.wwe_ppv_search_enabled' => false]);

        $show = $this->createWwePpv('Survivor Series 2001');

        $this->assertNull(app(NetflixWatchUrlResolver::class)->resolve($show));
    }

    private function createWwePpv(string $title): Show
    {
        $promotion = Promotion::factory()->wwe()->create();

        return Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => $title,
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);
    }
}

class WatchTargetResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_includes_youtube_and_netflix_targets_when_both_exist(): void
    {
        config(['streaming.netflix.wwe_ppv_search_enabled' => true]);

        $show = $this->createWwePpv('Survivor Series 2001');

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
            'external_id' => '80117477',
            'url' => 'https://www.netflix.com/watch/80117477',
            'is_primary' => true,
        ]);

        $targets = app(WatchTargetResolver::class)->resolveAll($show->fresh());

        $this->assertCount(2, $targets);
        $this->assertSame('youtube', $targets[0]->provider);
        $this->assertSame('netflix', $targets[1]->provider);
        $this->assertSame('deep_link', $targets[1]->mode);
    }

    private function createWwePpv(string $title): Show
    {
        $promotion = Promotion::factory()->wwe()->create();

        return Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => $title,
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);
    }
}
