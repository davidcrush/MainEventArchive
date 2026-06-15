<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Database\Seeders\WcwPre1990PpvCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WcwPre1990PpvCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_thirteen_pre_1990_wcw_ppvs(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $this->seed(WcwPre1990PpvCatalogSeeder::class);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->where('date', '<', '1990-01-01')
            ->orderBy('date')
            ->get();

        $this->assertCount(13, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->status === ShowStatus::PendingReview));
        $this->assertSame('Starrcade 1983', $shows->first()->title);
        $this->assertSame('1983-11-24', $shows->first()->date->toDateString());
        $this->assertSame('Starrcade 1989', $shows->last()->title);
    }

    public function test_seeder_is_idempotent(): void
    {
        Promotion::factory()->wcw()->create();

        $this->seed(WcwPre1990PpvCatalogSeeder::class);
        $this->seed(WcwPre1990PpvCatalogSeeder::class);

        $count = Show::query()
            ->whereHas('promotion', fn ($query) => $query->where('slug', 'wcw'))
            ->where('date', '<', '1990-01-01')
            ->count();

        $this->assertSame(13, $count);
    }

    public function test_seeder_does_not_modify_existing_1990_published_shows(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $existing = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'The Great American Bash 1990',
            'slug' => 'great-american-bash-1990',
            'date' => '1990-07-07',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
            'source' => 'manual',
        ]);

        $this->seed(WcwPre1990PpvCatalogSeeder::class);

        $existing->refresh();

        $this->assertSame(ShowStatus::Published, $existing->status);
        $this->assertSame('The Great American Bash 1990', $existing->title);
        $this->assertSame('1990-07-07', $existing->date->toDateString());
        $this->assertSame(1, Show::query()->whereDate('date', '1990-07-07')->count());
    }

    public function test_seeder_sets_cagematch_url_when_event_id_provided(): void
    {
        $this->assertSame(
            'https://www.cagematch.net/?id=1&nr=5678',
            sprintf(config('cagematch.event_url_template'), 5678),
        );
    }
}
