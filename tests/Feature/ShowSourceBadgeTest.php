<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowSourceBadgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_resource_includes_wikidata_source_fields(): void
    {
        $show = $this->createPublishedShow([
            'source' => 'wikidata',
            'source_id' => 'Q123456',
            'source_url' => 'https://www.wikidata.org/wiki/Q123456',
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.source', 'wikidata')
                ->where('show.source_id', 'Q123456')
                ->where('show.source_url', 'https://www.wikidata.org/wiki/Q123456'),
            );
    }

    public function test_wikidata_only_show_exposes_fields_for_wikidata_badge(): void
    {
        $show = $this->createPublishedShow([
            'source' => 'wikidata',
            'source_id' => 'Q123456',
            'source_url' => 'https://www.wikidata.org/wiki/Q123456',
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('show.source', 'wikidata')
                ->where('show.source_id', 'Q123456')
                ->where('show.source_url', 'https://www.wikidata.org/wiki/Q123456'),
            );
    }

    public function test_wikipedia_enriched_show_exposes_fields_for_both_badges(): void
    {
        $show = $this->createPublishedShow([
            'source' => 'wikidata',
            'source_id' => 'Q123456',
            'source_url' => 'https://en.wikipedia.org/wiki/Starrcade_(1996)',
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('show.source', 'wikidata')
                ->where('show.source_id', 'Q123456')
                ->where('show.source_url', 'https://en.wikipedia.org/wiki/Starrcade_(1996)'),
            );
    }

    public function test_manual_show_without_source_fields_has_no_source_provenance(): void
    {
        $show = $this->createPublishedShow([
            'source' => 'manual',
            'source_id' => null,
            'source_url' => null,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('show.source', 'manual')
                ->where('show.source_id', null)
                ->where('show.source_url', null),
            );
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function createPublishedShow(array $attributes = []): Show
    {
        $promotion = Promotion::factory()->wcw()->create();

        return Show::factory()->create(array_merge([
            'promotion_id' => $promotion->id,
            'status' => ShowStatus::Published,
            'title' => 'Starrcade 1997',
            'slug' => 'starrcade-1997-'.fake()->unique()->numerify('###'),
            'date' => '1997-12-28',
        ], $attributes));
    }
}
