<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shows\Pages\EditShow;
use App\Filament\Resources\Shows\RelationManagers\VideosRelationManager;
use App\Models\Show;
use App\Models\User;
use App\Models\Video;
use Filament\Actions\Testing\TestAction;
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

    public function test_admin_can_create_netflix_video_from_title_id(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create(['title' => 'Survivor Series 2001']);

        $this->actingAs($admin);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $show,
            'pageClass' => EditShow::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'provider' => 'netflix',
                'url' => '80117477',
                'title' => 'Survivor Series 2001',
                'is_primary' => true,
            ])
            ->assertHasNoFormErrors();

        $video = Video::query()->first();
        $this->assertNotNull($video);
        $this->assertSame('netflix', $video->provider);
        $this->assertSame('80117477', $video->external_id);
        $this->assertSame('https://www.netflix.com/watch/80117477', $video->url);
    }

    public function test_admin_can_create_youtube_video_from_watch_url(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create(['title' => 'Vengeance 2001']);

        $this->actingAs($admin);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $show,
            'pageClass' => EditShow::class,
        ])
            ->callAction(TestAction::make('create')->table(), data: [
                'provider' => 'youtube',
                'url' => 'https://www.youtube.com/watch?v=abc12345678',
                'title' => 'FULL EVENT: Vengeance 2001',
                'is_primary' => true,
            ])
            ->assertHasNoFormErrors();

        $video = Video::query()->where('provider', 'youtube')->first();
        $this->assertNotNull($video);
        $this->assertSame('abc12345678', $video->external_id);
        $this->assertSame('https://www.youtube.com/watch?v=abc12345678', $video->url);
    }

    public function test_admin_can_edit_and_delete_youtube_video(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create(['title' => 'Starrcade 2000']);
        $video = Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'PhqZycpMDSE',
            'url' => 'https://www.youtube.com/watch?v=PhqZycpMDSE',
            'is_primary' => true,
        ]);

        $this->actingAs($admin);

        Livewire::test(VideosRelationManager::class, [
            'ownerRecord' => $show,
            'pageClass' => EditShow::class,
        ])
            ->assertCanSeeTableRecords([$video])
            ->callAction(TestAction::make('edit')->table($video), data: [
                'provider' => 'youtube',
                'url' => 'https://youtu.be/abc12345678',
                'title' => 'Updated title',
                'is_primary' => true,
            ])
            ->assertHasNoFormErrors();

        $video->refresh();
        $this->assertSame('abc12345678', $video->external_id);
        $this->assertSame('Updated title', $video->title);
    }
}
