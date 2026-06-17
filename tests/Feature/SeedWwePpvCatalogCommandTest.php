<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedWwePpvCatalogCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_creates_one_hundred_twenty_wwe_ppvs_from_2002_to_2010(): void
    {
        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2002', '--to' => '2010'])
            ->assertExitCode(0);

        $promotion = Promotion::query()->where('slug', 'wwe')->first();

        $this->assertNotNull($promotion);

        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->whereBetween('date', ['2002-01-01', '2010-12-31'])
            ->orderBy('date')
            ->get();

        $this->assertCount(120, $shows);
        $this->assertTrue($shows->every(fn (Show $show) => $show->status === ShowStatus::PendingReview));
        $this->assertSame('Royal Rumble 2002', $shows->first()->title);
        $this->assertSame('2002-01-20', $shows->first()->date->toDateString());
        $this->assertSame('Over The Limit 2010', $shows->last()->title);
    }

    public function test_command_is_idempotent_for_2002_to_2010(): void
    {
        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2002', '--to' => '2010'])->assertExitCode(0);
        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2002', '--to' => '2010'])->assertExitCode(0);

        $count = Show::query()
            ->whereHas('promotion', fn ($query) => $query->where('slug', 'wwe'))
            ->whereBetween('date', ['2002-01-01', '2010-12-31'])
            ->count();

        $this->assertSame(120, $count);
    }

    public function test_command_dry_run_does_not_write_shows(): void
    {
        $this->artisan('shows:seed-wwe-ppv-catalog', [
            '--from' => '2002',
            '--to' => '2010',
            '--dry-run' => true,
        ])->assertExitCode(0);

        $this->assertSame(0, Show::query()->count());
    }

    public function test_scoped_delete_removes_manual_ppv_in_range_but_not_outside_range(): void
    {
        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2002', '--to' => '2010'])->assertExitCode(0);

        $promotion = Promotion::query()->where('slug', 'wwe')->firstOrFail();

        $published2000 = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Survivor Series 2000',
            'slug' => 'survivor-series-2000',
            'date' => '2000-11-19',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::Published,
        ]);

        $manual2005 = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Manual PPV 2005',
            'slug' => 'manual-ppv-2005',
            'date' => '2005-06-01',
            'show_type' => ShowType::Ppv,
            'status' => ShowStatus::PendingReview,
        ]);

        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2002', '--to' => '2010'])->assertExitCode(0);

        $this->assertDatabaseHas('shows', ['id' => $published2000->id]);
        $published2000->refresh();
        $this->assertSame(ShowStatus::Published, $published2000->status);

        $this->assertDatabaseMissing('shows', ['id' => $manual2005->id]);
    }

    public function test_command_rejects_invalid_year_range(): void
    {
        $this->artisan('shows:seed-wwe-ppv-catalog', ['--from' => '2010', '--to' => '2002'])
            ->assertExitCode(1);
    }
}
