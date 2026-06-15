<?php

namespace Tests\Feature;

use App\Enums\ShowStatus;
use App\Models\Promotion;
use App\Models\Show;
use App\Models\Video;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShowVideoLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_page_includes_primary_video_url(): void
    {
        $show = $this->createPublishedShow();

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'ftPK-rYz7Vc',
            'url' => 'https://www.youtube.com/watch?v=ftPK-rYz7Vc',
            'is_primary' => true,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.video.url', 'https://www.youtube.com/watch?v=ftPK-rYz7Vc'),
            );
    }

    public function test_show_page_without_videos_has_null_video(): void
    {
        $show = $this->createPublishedShow();

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->component('Shows/Show')
                ->where('show.video', null),
            );
    }

    public function test_show_page_falls_back_to_first_video_when_none_marked_primary(): void
    {
        $show = $this->createPublishedShow();

        Video::factory()->create([
            'show_id' => $show->id,
            'match_id' => null,
            'provider' => 'youtube',
            'external_id' => 'abc12345678',
            'url' => 'https://www.youtube.com/watch?v=abc12345678',
            'is_primary' => false,
        ]);

        $this->get(route('shows.show', $show->slug))
            ->assertOk()
            ->assertInertia(fn ($page) => $page
                ->where('show.video.url', 'https://www.youtube.com/watch?v=abc12345678'),
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
