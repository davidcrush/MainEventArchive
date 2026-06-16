<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportNetflixCatalogCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_matches_wwe_ppv_from_saved_html(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Survivor Series 2001',
            'date' => '2001-11-18',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $htmlPath = tempnam(sys_get_temp_dir(), 'netflix-html-');
        file_put_contents($htmlPath, <<<'HTML'
<a aria-label="Survivor Series 2001" href="/title/80117477">Survivor Series 2001</a>
HTML);

        $this->artisan('videos:import-netflix', [
            '--promotion' => 'wwe',
            '--html' => $htmlPath,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertSame(0, Video::query()->count());

        unlink($htmlPath);
    }

    public function test_import_creates_netflix_video_for_matched_show(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Survivor Series 2001',
            'date' => '2001-11-18',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $htmlPath = tempnam(sys_get_temp_dir(), 'netflix-html-');
        file_put_contents($htmlPath, <<<'HTML'
<a aria-label="Survivor Series 2001" href="/title/80117477">Survivor Series 2001</a>
HTML);

        $this->artisan('videos:import-netflix', [
            '--promotion' => 'wwe',
            '--html' => $htmlPath,
        ])->assertSuccessful();

        $video = Video::query()->first();
        $this->assertNotNull($video);
        $this->assertSame($show->id, $video->show_id);
        $this->assertSame('netflix', $video->provider);
        $this->assertSame('80117477', $video->external_id);

        unlink($htmlPath);
    }
}
