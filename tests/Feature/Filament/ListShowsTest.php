<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shows\Pages\ListShows;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ListShowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_nitro_tab_lists_only_nitro_episodes(): void
    {
        $admin = User::factory()->admin()->create();
        $nitro = Show::factory()->nitroEpisode(37)->create();
        $ppv = Show::factory()->starrcade1997()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->set('activeTab', 'nitro')
            ->assertCanSeeTableRecords([$nitro])
            ->assertCanNotSeeTableRecords([$ppv]);
    }

    public function test_pending_review_tab_lists_nitro_episodes_awaiting_publish(): void
    {
        $admin = User::factory()->admin()->create();
        $nitro = Show::factory()->nitroEpisode(18)->create();
        $published = Show::factory()->starrcade1997()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->set('activeTab', 'pending_review')
            ->assertCanSeeTableRecords([$nitro])
            ->assertCanNotSeeTableRecords([$published]);
    }
}
