<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shows\Pages\EditShow;
use App\Filament\Resources\Shows\RelationManagers\VideosRelationManager;
use App\Models\Show;
use App\Models\User;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class ShowVideosRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_linked_videos_on_show_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create(['title' => 'Halloween Havoc 1993']);
        $video = Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'ftPK-rYz7Vc',
            'url' => 'https://www.youtube.com/watch?v=ftPK-rYz7Vc',
            'title' => 'FULL EVENT: WCW Halloween Havoc 1993',
            'is_primary' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $show,
            'pageClass' => EditShow::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$video]);
    }
}
