<?php

namespace Tests\Unit\Services;

use App\Services\BrowseCache;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class BrowseCacheTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
    }

    public function test_invalidate_bumps_version_used_in_browse_keys(): void
    {
        $keyBefore = BrowseCache::browseKey('wcw', 'ppv', null, false);

        BrowseCache::invalidate();

        $keyAfter = BrowseCache::browseKey('wcw', 'ppv', null, false);

        $this->assertSame('browse.v1.wcw.ppv.all.all', $keyBefore);
        $this->assertSame('browse.v2.wcw.ppv.all.all', $keyAfter);
    }
}
