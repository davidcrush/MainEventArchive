<?php

namespace App\Services\Cagematch;

use App\Data\CagematchEvent;
use App\Data\CagematchShowLink;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Support\Collection;

class CagematchShowMatcher
{
    /**
     * @param  list<CagematchEvent>  $events
     * @return array{
     *     links: list<CagematchShowLink>,
     *     ambiguous: list<string>,
     *     unmatchedEvents: list<CagematchEvent>,
     *     unmatchedShows: Collection<int, Show>
     * }
     */
    public function match(
        Promotion $promotion,
        array $events,
        int $fromYear,
        int $toYear,
        ?string $slug = null,
    ): array {
        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->whereBetween('date', ["{$fromYear}-01-01", "{$toYear}-12-31"])
            ->when($slug !== null && $slug !== '', fn ($query) => $query->where('slug', $slug))
            ->whereNull('cagematch_url')
            ->orderBy('date')
            ->get();

        $links = [];
        $ambiguous = [];
        $matchedShowIds = [];
        $matchedEventIds = [];

        foreach ($events as $event) {
            if ($event->date->year < $fromYear || $event->date->year > $toYear) {
                continue;
            }

            $candidates = $shows->filter(function (Show $show) use ($event, $matchedShowIds): bool {
                if (isset($matchedShowIds[$show->id])) {
                    return false;
                }

                return $show->date->toDateString() === $event->date->toDateString()
                    && $this->titlesMatch($show->title, $event->title);
            })->values();

            if ($candidates->count() === 1) {
                $show = $candidates->first();
                $matchedShowIds[$show->id] = true;
                $matchedEventIds[$event->eventId] = true;
                $links[] = new CagematchShowLink(
                    $show,
                    $event,
                    sprintf(config('cagematch.event_url_template'), $event->eventId),
                );

                continue;
            }

            if ($candidates->count() > 1) {
                $ambiguous[] = "Ambiguous match for Cagematch event [{$event->title}] on {$event->date->toDateString()}.";
            }
        }

        $unmatchedEvents = array_values(array_filter(
            $events,
            fn (CagematchEvent $event): bool => ! isset($matchedEventIds[$event->eventId])
                && $event->date->year >= $fromYear
                && $event->date->year <= $toYear,
        ));

        $unmatchedShows = $shows->filter(fn (Show $show): bool => ! isset($matchedShowIds[$show->id]))->values();

        return [
            'links' => $links,
            'ambiguous' => $ambiguous,
            'unmatchedEvents' => $unmatchedEvents,
            'unmatchedShows' => $unmatchedShows,
        ];
    }

    private function titlesMatch(string $showTitle, string $eventTitle): bool
    {
        $normalizedShow = $this->normalizeTitle($showTitle);
        $normalizedEvent = $this->normalizeTitle($eventTitle);

        if ($normalizedShow === '' || $normalizedEvent === '') {
            return false;
        }

        if ($normalizedShow === $normalizedEvent) {
            return true;
        }

        return str_contains($normalizedShow, $normalizedEvent)
            || str_contains($normalizedEvent, $normalizedShow);
    }

    private function normalizeTitle(string $title): string
    {
        $title = preg_replace('/\s+\d{4}$/', '', trim($title)) ?? trim($title);
        $title = strtolower($title);
        $title = preg_replace('/[^a-z0-9]/', '', $title) ?? '';

        return $title;
    }
}
