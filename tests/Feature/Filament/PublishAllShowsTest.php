<?php

namespace Tests\Feature\Filament;

use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Filament\Resources\Shows\Pages\ListShows;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\User;
use App\Services\BrowseCache;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Livewire\Livewire;
use Tests\TestCase;

class PublishAllShowsTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_publish_all_pending_review_shows(): void
    {
        $admin = User::factory()->admin()->create();
        $pendingOne = Show::factory()->pendingReview()->create();
        $pendingTwo = Show::factory()->pendingReview()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->callAction('publishAll')
            ->assertNotified('Published');

        $pendingOne->refresh();
        $pendingTwo->refresh();

        $this->assertSame(ShowStatus::Published, $pendingOne->status);
        $this->assertSame(ShowStatus::Published, $pendingTwo->status);
        $this->assertSame($admin->id, $pendingOne->verified_by);
        $this->assertSame($admin->id, $pendingTwo->verified_by);
        $this->assertNotNull($pendingOne->verified_at);
        $this->assertNotNull($pendingTwo->verified_at);
    }

    public function test_publish_all_leaves_draft_shows_untouched(): void
    {
        $admin = User::factory()->admin()->create();
        $pending = Show::factory()->pendingReview()->create();
        $draft = Show::factory()->draft()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->callAction('publishAll')
            ->assertNotified('Published');

        $pending->refresh();
        $draft->refresh();

        $this->assertSame(ShowStatus::Published, $pending->status);
        $this->assertSame(ShowStatus::Draft, $draft->status);
    }

    public function test_publish_all_action_is_hidden_when_no_pending_review_shows(): void
    {
        $admin = User::factory()->admin()->create();
        Show::factory()->create();

        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->assertActionHidden('publishAll');
    }

    public function test_publish_all_invalidates_browse_cache_once(): void
    {
        Cache::flush();

        $promotion = Promotion::factory()->wcw()->create();
        Show::factory()->pendingReview()->create([
            'promotion_id' => $promotion->id,
            'show_type' => ShowType::Ppv,
            'date' => '1997-12-28',
        ]);
        Show::factory()->pendingReview()->create([
            'promotion_id' => $promotion->id,
            'show_type' => ShowType::Ppv,
            'date' => '1996-07-07',
        ]);

        $keyBefore = BrowseCache::browseKey('wcw', 'ppv', null, false);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('shows', 0));

        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        Livewire::test(ListShows::class)
            ->callAction('publishAll')
            ->assertNotified('Published');

        $keyAfter = BrowseCache::browseKey('wcw', 'ppv', null, false);

        $this->assertSame('browse.v1.wcw.ppv.all.all', $keyBefore);
        $this->assertSame('browse.v2.wcw.ppv.all.all', $keyAfter);

        $this->get(route('browse'))
            ->assertOk()
            ->assertInertia(fn ($page) => $page->has('shows', 2));
    }
}
