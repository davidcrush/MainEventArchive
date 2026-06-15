<?php

namespace Tests\Feature\Filament;

use App\Filament\Resources\Venues\Pages\EditVenue;
use App\Filament\Resources\Venues\Pages\ListVenues;
use App\Models\User;
use App\Models\Venue;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;
use Tests\TestCase;

class VenueResourceTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_view_venues_list(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create(['name' => 'Ocean Center']);

        $this->actingAs($admin);

        Livewire::test(ListVenues::class)
            ->assertCanSeeTableRecords([$venue]);
    }

    public function test_admin_can_reimport_venue_from_wikipedia(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create([
            'name' => 'Ocean Center',
            'wikipedia_page_title' => 'Ocean Center',
            'capacity' => 5000,
        ]);

        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'pages' => [
                        '1' => [
                            'pageid' => 1,
                            'title' => 'Ocean Center',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => <<<'WIKI'
{{Infobox convention center
| name = Ocean Center
| city = Daytona Beach
| state = Florida
| country = United States
| capacity = 8,500
}}
WIKI,
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $this->actingAs($admin);

        Livewire::test(EditVenue::class, ['record' => $venue->getKey()])
            ->callAction('reimportFromWikipedia')
            ->assertNotified('Venue updated from Wikipedia');

        $venue->refresh();

        $this->assertSame(8500, $venue->capacity);
        $this->assertSame('Daytona Beach', $venue->city);
    }

    public function test_reimport_action_is_hidden_without_wikipedia_page_title(): void
    {
        $admin = User::factory()->admin()->create();
        $venue = Venue::factory()->create([
            'wikipedia_page_title' => '',
        ]);

        $this->actingAs($admin);

        Livewire::test(EditVenue::class, ['record' => $venue->getKey()])
            ->assertActionHidden('reimportFromWikipedia');
    }
}
