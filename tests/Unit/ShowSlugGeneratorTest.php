<?php

namespace Tests\Unit;

use App\Models\Show;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowSlugGeneratorTest extends TestCase
{
    use RefreshDatabase;

    private ShowSlugGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $this->generator = app(ShowSlugGenerator::class);
    }

    public function test_generates_base_slug_with_year(): void
    {
        $slug = $this->generator->generate('Starrcade 1997', Carbon::parse('1997-12-28'));

        $this->assertSame('starrcade-1997', $slug);
    }

    public function test_appends_month_on_collision(): void
    {
        Show::factory()->create([
            'title' => 'No Mercy',
            'slug' => 'no-mercy-1999',
            'date' => '1999-05-16',
        ]);

        $maySlug = $this->generator->generate('No Mercy', Carbon::parse('1999-05-16'));
        $octSlug = $this->generator->generate('No Mercy', Carbon::parse('1999-10-17'));

        $this->assertSame('no-mercy-1999-may', $maySlug);
        $this->assertSame('no-mercy-1999-oct', $octSlug);
    }

    public function test_appends_day_when_month_collision(): void
    {
        Show::factory()->create([
            'title' => 'Live Event',
            'slug' => 'live-event-1999-jan',
            'date' => '1999-01-15',
        ]);

        $slug = $this->generator->generate('Live Event', Carbon::parse('1999-01-15'));

        $this->assertSame('live-event-1999-jan-15', $slug);
    }
}
