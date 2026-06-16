<?php

namespace Tests\Unit;

use App\Services\Wrestling\WrestleManiaEditionResolver;
use PHPUnit\Framework\TestCase;

class WrestleManiaEditionResolverTest extends TestCase
{
    private WrestleManiaEditionResolver $resolver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->resolver = new WrestleManiaEditionResolver;
    }

    public function test_parses_streaming_edition_numbers(): void
    {
        $this->assertSame(['edition' => 12], $this->resolver->parseStreamingTitle('WrestleMania 12'));
        $this->assertSame(['edition' => 17], $this->resolver->parseStreamingTitle('WrestleMania 17'));
        $this->assertSame(['edition' => 16], $this->resolver->parseStreamingTitle('WrestleMania 2000'));
    }

    public function test_strips_multi_night_suffixes_from_streaming_titles(): void
    {
        $this->assertSame(['edition' => 41], $this->resolver->parseStreamingTitle('WrestleMania 41 Sunday'));
        $this->assertSame(['edition' => 37], $this->resolver->parseStreamingTitle('WrestleMania 37 - Night 2'));
    }

    public function test_ignores_non_wrestlemania_titles(): void
    {
        $this->assertNull($this->resolver->parseStreamingTitle('WWE Road to WrestleMania'));
        $this->assertNull($this->resolver->parseStreamingTitle('WWE WrestleMania'));
    }

    public function test_extracts_catalog_editions(): void
    {
        $this->assertSame(12, $this->resolver->extractCatalogEdition('WrestleMania XII 1996'));
        $this->assertSame(13, $this->resolver->extractCatalogEdition('WrestleMania 13 1997'));
        $this->assertSame(14, $this->resolver->extractCatalogEdition('WrestleMania XIV 1998'));
        $this->assertSame(15, $this->resolver->extractCatalogEdition('WrestleMania XV 1999'));
        $this->assertSame(16, $this->resolver->extractCatalogEdition('WrestleMania 2000'));
        $this->assertSame(17, $this->resolver->extractCatalogEdition('WrestleMania X-Seven 2001'));
    }

    public function test_matches_streaming_edition_to_catalog_title(): void
    {
        $this->assertTrue($this->resolver->matchesEdition(12, 'WrestleMania XII 1996'));
        $this->assertTrue($this->resolver->matchesEdition(17, 'WrestleMania X-Seven 2001'));
        $this->assertFalse($this->resolver->matchesEdition(17, 'WrestleMania 2000'));
    }
}
