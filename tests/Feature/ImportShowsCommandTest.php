<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ImportShowsCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_creates_pending_review_shows_with_provenance(): void
    {
        Http::fake([
            'query.wikidata.org/*' => Http::response([
                'results' => [
                    'bindings' => [
                        [
                            'event' => ['value' => 'http://www.wikidata.org/entity/Q12345'],
                            'eventLabel' => ['value' => 'Starrcade 1993'],
                            'date' => ['value' => '1993-12-27T00:00:00Z'],
                            'venueLabel' => ['value' => 'Independence Arena'],
                            'cityLabel' => ['value' => 'Charlotte'],
                        ],
                    ],
                ],
            ]),
        ]);

        Promotion::factory()->wcw()->create();

        $this->artisan('shows:import wikidata --from=1993 --to=1996')
            ->assertSuccessful();

        $show = Show::query()->where('source_id', 'Q12345')->first();

        $this->assertNotNull($show);
        $this->assertSame('Starrcade 1993', $show->title);
        $this->assertSame(ShowStatus::PendingReview, $show->status);
        $this->assertSame('wikidata', $show->source);
        $this->assertSame('https://www.wikidata.org/wiki/Q12345', $show->source_url);
        $this->assertNotNull($show->imported_at);
    }
}
