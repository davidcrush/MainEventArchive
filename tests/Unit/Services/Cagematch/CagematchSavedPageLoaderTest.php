<?php

namespace Tests\Unit\Services\Cagematch;

use App\Services\Cagematch\CagematchListingParser;
use App\Services\Cagematch\CagematchSavedPageLoader;
use Tests\TestCase;

class CagematchSavedPageLoaderTest extends TestCase
{
    public function test_loads_plain_html_unchanged(): void
    {
        $html = file_get_contents(base_path('tests/fixtures/cagematch/wcw-ppv-listing.html'));

        $path = tempnam(sys_get_temp_dir(), 'cagematch-plain-');
        file_put_contents($path, $html);

        $loaded = (new CagematchSavedPageLoader)->load($path);

        $this->assertSame($html, $loaded);

        unlink($path);
    }

    public function test_extracts_html_from_mhtml_save(): void
    {
        $loaded = (new CagematchSavedPageLoader)->load(
            base_path('docs/third-party/cagematch/WWE-PPVs-2003-1996.html'),
        );

        $events = (new CagematchListingParser)->parse($loaded);

        $this->assertGreaterThanOrEqual(80, count($events));
        $this->assertSame('WWF Royal Rumble 2001', collect($events)->firstWhere(
            fn ($event) => $event->date->toDateString() === '2001-01-21',
        )?->title);
    }
}
