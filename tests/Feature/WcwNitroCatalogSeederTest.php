<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Database\Seeders\WcwNitroCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WcwNitroCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_fifty_one_nitro_tv_shows_for_1996(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $this->seed(WcwNitroCatalogSeeder::class);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Tv)
            ->where('title', 'like', 'WCW Monday Nitro%')
            ->whereBetween('date', ['1996-01-01', '1996-12-31'])
            ->orderBy('date')
            ->get();

        $this->assertCount(51, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->status === ShowStatus::PendingReview));
        $this->assertSame('WCW Monday Nitro #18', $shows->first()->title);
        $this->assertSame(18, $shows->first()->episode_number);
        $this->assertSame('1996-01-01', $shows->first()->date->toDateString());
        $this->assertSame('The Omni', $shows->first()->venue);
        $this->assertSame('WCW Monday Nitro #68', $shows->last()->title);
        $this->assertSame('1996-12-30', $shows->last()->date->toDateString());
    }

    public function test_seeder_is_idempotent(): void
    {
        Promotion::factory()->wcw()->create();

        $this->seed(WcwNitroCatalogSeeder::class);
        $this->seed(WcwNitroCatalogSeeder::class);

        $count = Show::query()
            ->whereHas('promotion', fn ($query) => $query->where('slug', 'wcw'))
            ->where('title', 'like', 'WCW Monday Nitro%')
            ->whereBetween('date', ['1996-01-01', '1996-12-31'])
            ->count();

        $this->assertSame(51, $count);
    }

    public function test_seeder_does_not_modify_clash_show_on_same_date(): void
    {
        $promotion = Promotion::factory()->wcw()->create();

        $clash = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Clash of the Champions X',
            'slug' => 'clash-of-the-champions-x-1990',
            'date' => '1990-02-06',
            'show_type' => ShowType::Tv,
            'status' => ShowStatus::Published,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'WCW Monday Nitro #24',
            'slug' => 'wcw-monday-nitro-24-1996',
            'date' => '1996-02-12',
            'episode_number' => 24,
            'show_type' => ShowType::Tv,
        ]);

        $this->seed(WcwNitroCatalogSeeder::class);

        $clash->refresh();

        $this->assertSame('Clash of the Champions X', $clash->title);
        $this->assertSame(1, Show::query()->whereDate('date', '1990-02-06')->where('title', 'like', 'Clash%')->count());
        $this->assertSame(51, Show::query()->where('title', 'like', 'WCW Monday Nitro%')->whereYear('date', 1996)->count());
    }
}
