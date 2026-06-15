<?php

namespace Tests\Feature\Filament;

use App\Enums\ShowStatus;
use App\Filament\Resources\Shows\Pages\EditShow;
use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class PublishShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_show_from_edit_page(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->pendingReview()->create();

        $this->actingAs($admin);

        Livewire::test(EditShow::class, ['record' => $show->getKey()])
            ->callAction('publish')
            ->assertNotified('Published');

        $show->refresh();

        $this->assertSame(ShowStatus::Published, $show->status);
        $this->assertNotNull($show->verified_at);
        $this->assertSame($admin->id, $show->verified_by);
    }

    public function test_publish_action_is_hidden_for_published_show(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create();

        $this->actingAs($admin);

        Livewire::test(EditShow::class, ['record' => $show->getKey()])
            ->assertActionHidden('publish');
    }
}
