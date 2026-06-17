<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shows\Pages\ListShows;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListShowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_wcw_tab_lists_only_wcw_shows(): void
    {
        $admin = User::factory()->admin()->create();
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();
        $wcwShow = Show::factory()->nitroEpisode(37)->create(['promotion_id' => $wcw->id]);
        $wweShow = Show::factory()->create(['promotion_id' => $wwe->id]);

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->set('activeTab', 'wcw')
            ->assertCanSeeTableRecords([$wcwShow])
            ->assertCanNotSeeTableRecords([$wweShow]);
    }

    public function test_wwe_tab_lists_only_wwe_shows(): void
    {
        $admin = User::factory()->admin()->create();
        $wcw = Promotion::factory()->wcw()->create();
        $wwe = Promotion::factory()->wwe()->create();
        $wcwShow = Show::factory()->starrcade1997()->create(['promotion_id' => $wcw->id]);
        $wweShow = Show::factory()->create(['promotion_id' => $wwe->id]);

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->set('activeTab', 'wwe')
            ->assertCanSeeTableRecords([$wweShow])
            ->assertCanNotSeeTableRecords([$wcwShow]);
    }

    public function test_pending_review_tab_lists_shows_awaiting_publish(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = Show::factory()->nitroEpisode(18)->create();
        $published = Show::factory()->starrcade1997()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->set('activeTab', 'pending_review')
            ->assertCanSeeTableRecords([$pending])
            ->assertCanNotSeeTableRecords([$published]);
    }
}
