<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Shows\Pages\EditShow;
use App\Filament\Resources\Shows\RelationManagers\MatchesRelationManager;
use App\Models\Show;
use App\Models\User;
use App\Models\WrestlingMatch;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MatchesRelationManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_card_relation_manager_shows_pre_show_badge_for_off_card_matches(): void
    {
        $admin = User::factory()->admin()->create();
        $show = Show::factory()->create();
        $onCardMatch = WrestlingMatch::factory()->create([
            'show_id' => $show->id,
            'card_order' => 1,
            'is_ppv' => true,
        ]);
        $preShowMatch = WrestlingMatch::factory()->create([
            'show_id' => $show->id,
            'card_order' => 2,
            'is_ppv' => false,
        ]);

        $this->actingAs($admin);

        Livewire::test(MatchesRelationManager::class, [
            'ownerRecord' => $show,
            'pageClass' => EditShow::class,
        ])
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$onCardMatch, $preShowMatch])
            ->assertSee('On card')
            ->assertSee('Pre-show');
    }
}
