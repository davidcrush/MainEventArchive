<?php

namespace Tests\Feature;

use App\Models\Show;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthGatesTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_rate(): void
    {
        $show = Show::factory()->create();

        $this->post(route('ratings.store'), [
            'rateable_type' => 'show',
            'rateable_id' => $show->id,
            'stars' => 5,
        ])->assertRedirect(route('login'));
    }

    public function test_unverified_user_cannot_access_watchlist(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get(route('watchlist.index'))
            ->assertRedirect(route('verification.notice'));
    }

    public function test_verified_user_can_add_to_watchlist(): void
    {
        $user = User::factory()->create();
        $show = Show::factory()->create();

        $this->actingAs($user)
            ->post(route('watchlist.store', $show))
            ->assertRedirect();

        $this->assertDatabaseHas('watchlist_items', [
            'user_id' => $user->id,
            'show_id' => $show->id,
        ]);
    }
}
