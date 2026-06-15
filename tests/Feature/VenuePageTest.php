<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Venue;
use App\Models\VenueAlias;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VenuePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_venue_page_renders_metadata_aliases_and_published_shows(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $venue = Venue::factory()->create([
            'name' => 'Allstate Arena',
            'slug' => 'allstate-arena',
            'city' => 'Rosemont',
            'state_province' => 'Illinois',
            'country' => 'United States',
            'capacity' => 18500,
            'wikipedia_url' => 'https://en.wikipedia.org/wiki/Allstate_Arena',
        ]);

        VenueAlias::query()->create([
            'venue_id' => $venue->id,
            'name' => 'Allstate Arena',
            'source' => 'wikipedia_infobox',
        ]);
        VenueAlias::query()->create([
            'venue_id' => $venue->id,
            'name' => 'Rosemont Horizon',
            'source' => 'show_infobox',
        ]);

        $publishedShow = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'venue_id' => $venue->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
            'status' => ShowStatus::Published,
        ]);

        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'venue_id' => $venue->id,
            'title' => 'Draft Show',
            'slug' => 'draft-show',
            'date' => '1996-01-01',
            'status' => ShowStatus::Draft,
        ]);

        $this->get(route('venues.show', $venue->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Venues/Show')
                ->where('venue.name', 'Allstate Arena')
                ->where('venue.slug', 'allstate-arena')
                ->where('venue.location', 'Rosemont, Illinois, United States')
                ->where('venue.capacity', 18500)
                ->where('venue.wikipedia_url', 'https://en.wikipedia.org/wiki/Allstate_Arena')
                ->has('venue.aliases', 1)
                ->where('venue.aliases.0.name', 'Rosemont Horizon')
                ->has('shows', 1)
                ->where('shows.0.slug', $publishedShow->slug),
            );
    }

    public function test_unknown_venue_slug_returns_not_found(): void
    {
        $this->get(route('venues.show', 'missing-venue'))
            ->assertNotFound();
    }

    public function test_show_page_exposes_linked_venue_for_show_with_venue_id(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $venue = Venue::factory()->create([
            'name' => 'Ocean Center',
            'slug' => 'ocean-center',
        ]);

        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'venue_id' => $venue->id,
            'venue' => 'Ocean Center',
            'status' => ShowStatus::Published,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.venue.name', 'Ocean Center')
                ->where('show.venue.slug', 'ocean-center'),
            );
    }

    public function test_show_page_keeps_text_only_venue_when_not_linked(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'venue_id' => null,
            'venue' => 'Undocumented Arena',
            'status' => ShowStatus::Published,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.venue', 'Undocumented Arena'),
            );
    }
}
