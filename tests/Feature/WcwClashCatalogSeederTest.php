<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Database\Seeders\WcwClashCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WcwClashCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_thirty_one_clash_tv_shows(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $this->seed(WcwClashCatalogSeeder::class);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Tv)
            ->whereBetween('date', ['1989-01-01', '1997-12-31'])
            ->orderBy('date')
            ->get();

        $this->assertCount(31, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->status === ShowStatus::PendingReview));
        $this->assertSame('Clash of the Champions V', $shows->first()->title);
        $this->assertSame('1989-02-15', $shows->first()->date->toDateString());
        $this->assertSame('Clash of the Champions XXXV', $shows->last()->title);
    }

    public function test_seeder_is_idempotent(): void
    {
        Promotion::factory()->wcw()->create();

        $this->seed(WcwClashCatalogSeeder::class);
        $this->seed(WcwClashCatalogSeeder::class);

        $count = Show::query()
            ->whereHas('promotion', fn ($query) => $query->where('slug', 'wcw'))
            ->where('show_type', ShowType::Tv)
            ->whereBetween('date', ['1989-01-01', '1997-12-31'])
            ->count();

        $this->assertSame(31, $count);
    }

    public function test_seeder_does_not_modify_existing_ppv_on_same_date(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $ppv = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WrestleWar 1989',
            'slug' => 'wrestlewar-1989',
            'date' => '1989-02-19',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $this->seed(WcwClashCatalogSeeder::class);

        $ppv->refresh();

        $this->assertSame(ShowType::Ppv, $ppv->show_type);
        $this->assertSame('WrestleWar 1989', $ppv->title);
        $this->assertSame(1, Show::query()->whereDate('date', '1989-02-19')->where('show_type', ShowType::Ppv)->count());
        $this->assertSame(1, Show::query()->whereDate('date', '1989-02-15')->where('show_type', ShowType::Tv)->count());
    }
}
