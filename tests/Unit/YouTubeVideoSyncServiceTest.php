<?php

namespace Tests\Unit;

use App\Data\YouTubePlaylistEntry;
use App\Models\Show;
use App\Models\Video;
use App\Services\YouTube\YouTubeVideoSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class YouTubeVideoSyncServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_is_idempotent_and_demotes_other_primary_videos(): void
    {
        $show = Show::factory()->create();

        $service = new YouTubeVideoSyncService;

        $entry = new YouTubePlaylistEntry(
            'ftPK-rYz7Vc',
            'FULL EVENT: WCW Halloween Havoc 1993 | Cactus Jack and Vader Spin the Wheel, Make the Deal',
        );

        $this->assertSame('created', $service->sync($show, $entry));
        $this->assertSame('updated', $service->sync($show, $entry));
        $this->assertSame(1, Video::query()->count());

        $video = Video::query()->first();
        $this->assertSame('youtube', $video->provider);
        $this->assertSame('ftPK-rYz7Vc', $video->external_id);
        $this->assertTrue($video->is_primary);

        $olderVideo = Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'olderVideo1',
            'url' => 'https://www.youtube.com/watch?v=olderVideo1',
            'is_primary' => true,
        ]);

        $service->sync($show, $entry);

        $this->assertFalse($olderVideo->fresh()->is_primary);
        $this->assertTrue($video->fresh()->is_primary);
    }
}
