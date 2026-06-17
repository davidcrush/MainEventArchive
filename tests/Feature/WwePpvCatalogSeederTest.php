<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Database\Seeders\WwePpvCatalogSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WwePpvCatalogSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_seeder_creates_eighty_wwe_ppvs_from_1996_to_2001(): void
    {
        $this->seed(WwePpvCatalogSeeder::class);

        $promotion = Promotion::query()->where('slug', 'wwe')->first();

        $this->assertNotNull($promotion);
        $this->assertSame('World Wrestling Entertainment', $promotion->name);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->whereBetween('date', ['1996-01-01', '2001-12-31'])
            ->orderBy('date')
            ->get();

        $this->assertCount(81, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->status === ShowStatus::PendingReview));
        $this->assertSame('Royal Rumble 1996', $shows->first()->title);
        $this->assertSame('1996-01-21', $shows->first()->date->toDateString());
        $this->assertSame('Vengeance 2001', $shows->last()->title);
    }

    public function test_seeder_is_idempotent(): void
    {
        $this->seed(WwePpvCatalogSeeder::class);
        $this->seed(WwePpvCatalogSeeder::class);

        $count = Show::query()
            ->whereHas('promotion', fn ($query) => $query->where('slug', 'wwe'))
            ->whereBetween('date', ['1996-01-01', '2001-12-31'])
            ->count();

        $this->assertSame(81, $count);
    }

    public function test_seeder_does_not_modify_wcw_shows_on_same_date(): void
    {
        $wcw = Promotion::factory()->wcw()->create();

        $wcwShow = Show::factory()->create([
            'promotion_id' => $wcw->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $this->seed(WwePpvCatalogSeeder::class);

        $wcwShow->refresh();

        $this->assertSame('Starrcade 1996', $wcwShow->title);
        $this->assertSame(ShowStatus::Published, $wcwShow->status);
    }
}
