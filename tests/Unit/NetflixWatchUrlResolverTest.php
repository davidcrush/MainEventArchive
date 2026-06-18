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

    public function test_returns_search_url_for_wwe_ppv(): void
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

    public function test_returns_null_for_wcw_ppv(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $this->assertNull(app(NetflixWatchUrlResolver::class)->resolve($show));
    }

    public function test_returns_null_when_search_disabled(): void
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

    public function test_includes_youtube_and_netflix_targets_for_wwe_ppv_with_youtube(): void
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

        $targets = app(WatchTargetResolver::class)->resolveAll($show->fresh());

        $this->assertCount(2, $targets);
        $this->assertSame('youtube', $targets[0]->provider);
        $this->assertSame('netflix', $targets[1]->provider);
        $this->assertSame('search', $targets[1]->mode);
        $this->assertSame(
            'https://www.netflix.com/search?q=Survivor%20Series%202001',
            $targets[1]->url,
        );
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
