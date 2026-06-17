<?php

namespace Tests\Feature;

use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SyncYouTubePlaylistCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_does_not_write_videos(): void
    {
        config(['youtube.api_key' => 'test-api-key']);

        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Halloween Havoc 1993',
            'date' => '1993-10-24',
            'show_type' => ShowType::Ppv,
        ]);

        Http::fake([
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'FULL EVENT: WCW Halloween Havoc 1993 | Cactus Jack and Vader Spin the Wheel, Make the Deal',
                            'resourceId' => ['videoId' => 'ftPK-rYz7Vc'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('videos:sync-youtube-playlist', [
            '--promotion' => 'wcw',
            '--source' => 'api',
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Video::query()->count());
    }

    public function test_sync_creates_video_from_api_response(): void
    {
        config(['youtube.api_key' => 'test-api-key']);

        $promotion = Promotion::factory()->wcw()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Halloween Havoc 1993',
            'date' => '1993-10-24',
            'show_type' => ShowType::Ppv,
        ]);

        Http::fake([
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'FULL EVENT: WCW Halloween Havoc 1993 | Cactus Jack and Vader Spin the Wheel, Make the Deal',
                            'resourceId' => ['videoId' => 'ftPK-rYz7Vc'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('videos:sync-youtube-playlist', [
            '--promotion' => 'wcw',
            '--source' => 'api',
        ])->assertSuccessful();

        $this->assertSame(1, Video::query()->count());

        $video = Video::query()->first();
        $this->assertSame('ftPK-rYz7Vc', $video->external_id);
        $this->assertSame('https://www.youtube.com/watch?v=ftPK-rYz7Vc', $video->url);
        $this->assertTrue($video->is_primary);
    }

    public function test_html_source_requires_html_path(): void
    {
        Promotion::factory()->wcw()->create();

        $this->artisan('videos:sync-youtube-playlist', [
            '--promotion' => 'wcw',
            '--source' => 'html',
        ])->assertFailed();
    }

    public function test_sync_wwe_playlist_creates_video_from_api_response(): void
    {
        config(['youtube.api_key' => 'test-api-key']);

        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Royal Rumble 1991',
            'date' => '1991-01-19',
            'show_type' => ShowType::Ppv,
        ]);

        Http::fake([
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'FULL EVENT: Royal Rumble 1991 | Warrior vs. Slaughter; 30-Man Royal Rumble Match and MORE',
                            'resourceId' => ['videoId' => 'P-0JmpmHf4A'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('videos:sync-youtube-playlist', [
            '--promotion' => 'wwe',
            '--playlist' => 'wwe_ppv',
            '--source' => 'api',
        ])->assertSuccessful();

        $this->assertSame(1, Video::query()->count());
        $this->assertSame('P-0JmpmHf4A', Video::query()->value('external_id'));
    }

    public function test_sync_wwe_nxt_playlist_creates_video_from_api_response(): void
    {
        config(['youtube.api_key' => 'test-api-key']);

        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'NXT Deadline 2024',
            'date' => '2024-12-07',
            'show_type' => ShowType::Ppv,
        ]);

        Http::fake([
            'www.googleapis.com/youtube/v3/playlistItems*' => Http::response([
                'items' => [
                    [
                        'snippet' => [
                            'title' => 'FULL EVENT: NXT Deadline 2024 | Iron Survivor Challenge',
                            'resourceId' => ['videoId' => 'nxt-deadline-24'],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->artisan('videos:sync-youtube-playlist', [
            '--promotion' => 'wwe',
            '--playlist' => 'wwe_nxt',
            '--source' => 'api',
        ])->assertSuccessful();

        $this->assertSame(1, Video::query()->count());
        $this->assertSame('nxt-deadline-24', Video::query()->value('external_id'));
    }
}
