<?php

namespace Tests\Unit;

use App\Data\CagematchEvent;
use App\Importers\WwePpvCatalogImporter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

class WwePpvCatalogImporterTest extends TestCase
{
    use RefreshDatabase;

    public function test_load_events_prefers_wrestlemania_over_nxt_stand_and_deliver_on_same_date(): void
    {
        $events = $this->loadEventsForYearRange(2021, 2026);

        $byDate = collect($events)->keyBy(fn ($event) => $event->date->toDateString());

        $this->assertSame('WWE WrestleMania 38 - Saturday', $byDate->get('2022-04-02')?->title);
        $this->assertSame('WWE WrestleMania 39 - Saturday', $byDate->get('2023-04-01')?->title);
        $this->assertSame('WWE WrestleMania XL - Saturday', $byDate->get('2024-04-06')?->title);
        $this->assertSame('WWE WrestleMania 41 - Saturday', $byDate->get('2025-04-19')?->title);
    }

    public function test_load_events_returns_one_hundred_six_events_for_2021_to_2026(): void
    {
        $events = $this->loadEventsForYearRange(2021, 2026);

        $this->assertCount(106, $events);
    }

    /**
     * @return list<CagematchEvent>
     */
    private function loadEventsForYearRange(int $fromYear, int $toYear): array
    {
        $importer = app(WwePpvCatalogImporter::class);
        $method = new ReflectionMethod(WwePpvCatalogImporter::class, 'loadEvents');
        $method->setAccessible(true);

        /** @var list<CagematchEvent> $events */
        $events = $method->invoke($importer, $fromYear, $toYear);

        return $events;
    }
}
