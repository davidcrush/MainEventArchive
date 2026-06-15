<?php

namespace Tests\Feature;

use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LinkCagematchShowsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_links_matching_shows_without_persisting(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
            'cagematch_url' => null,
        ]);

        $fixture = base_path('tests/fixtures/cagematch/wcw-ppv-listing.html');

        $this->artisan('shows:link-cagematch', [
            '--promotion' => 'wcw',
            '--from' => '1995',
            '--to' => '1996',
            '--html' => $fixture,
            '--dry-run' => true,
        ])->assertSuccessful();

        $this->assertNull($show->refresh()->cagematch_url);
    }

    public function test_command_persists_cagematch_url_from_saved_html_file(): void
    {
        $promotion = Promotion::factory()->wcw()->create();
        $show = Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'Starrcade 1996',
            'slug' => 'starrcade-1996',
            'date' => '1996-12-29',
            'show_type' => ShowType::Ppv,
            'cagematch_url' => null,
        ]);

        $fixture = base_path('tests/fixtures/cagematch/wcw-ppv-listing.html');

        $this->artisan('shows:link-cagematch', [
            '--promotion' => 'wcw',
            '--from' => '1995',
            '--to' => '1996',
            '--html' => $fixture,
        ])->assertSuccessful();

        $this->assertSame(
            'https://www.cagematch.net/?id=1&nr=1001',
            $show->refresh()->cagematch_url,
        );
    }
}
