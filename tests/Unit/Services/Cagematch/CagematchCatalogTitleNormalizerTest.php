<?php

namespace Tests\Unit\Services\Cagematch;

use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use Carbon\Carbon;
use Tests\TestCase;

class CagematchCatalogTitleNormalizerTest extends TestCase
{
    private CagematchCatalogTitleNormalizer $normalizer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->normalizer = new CagematchCatalogTitleNormalizer;
    }

    public function test_strips_wwf_prefix_from_recurring_ppv_title(): void
    {
        $title = $this->normalizer->normalize(
            'WWF Royal Rumble 1996',
            Carbon::parse('1996-01-21'),
        );

        $this->assertSame('Royal Rumble 1996', $title);
    }

    public function test_strips_wwe_prefix_from_recurring_ppv_title(): void
    {
        $title = $this->normalizer->normalize(
            'WWE Survivor Series 2002',
            Carbon::parse('2002-11-17'),
        );

        $this->assertSame('Survivor Series 2002', $title);
    }

    public function test_normalizes_in_your_house_title_without_year_suffix(): void
    {
        $title = $this->normalizer->normalize(
            'WWF In Your House 12: It\'s Time',
            Carbon::parse('1996-12-15'),
        );

        $this->assertSame('In Your House 12: It\'s Time 1996', $title);
    }

    public function test_normalizes_in_your_house_title_when_cagematch_subtitle_already_includes_year(): void
    {
        $title = $this->normalizer->normalize(
            'WWF In Your House 21: Unforgiven 1998',
            Carbon::parse('1998-04-26'),
        );

        $this->assertSame('In Your House 21: Unforgiven 1998', $title);
    }

    public function test_strips_subtitle_suffix_after_dash(): void
    {
        $title = $this->normalizer->normalize(
            'WWF Survivor Series 1998 - "Deadly Game"',
            Carbon::parse('1998-11-15'),
        );

        $this->assertSame('Survivor Series 1998', $title);
    }
}
