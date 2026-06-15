<?php

namespace Tests\Unit\Services\Wikipedia;

use App\Models\Show;
use App\Services\Wikipedia\WikipediaPageTitleResolver;
use Tests\TestCase;

class WikipediaPageTitleResolverTest extends TestCase
{
    public function test_in_your_house_title_includes_numbered_wikipedia_candidate(): void
    {
        $show = new Show([
            'title' => 'In Your House 12: It\'s Time 1996',
        ]);

        $candidates = (new WikipediaPageTitleResolver)->candidates($show);

        $this->assertContains('In Your House 12: It\'s Time (1996)', $candidates);
        $this->assertContains('In Your House 12: It\'s Time 1996', $candidates);
    }
}
