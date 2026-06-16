<?php

namespace App\Services\YouTube;

use App\Data\YouTubePlaylistEntry;
use App\Data\YouTubeShowVideoLink;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Cagematch\CagematchCatalogTitleNormalizer;
use App\Services\CatalogTitleMatcher;
use App\Services\Wrestling\WrestleManiaEditionResolver;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class YouTubeShowMatcher
{
    public function __construct(
        private YouTubeTitleParser $titleParser,
        private CagematchCatalogTitleNormalizer $titleNormalizer,
        private CatalogTitleMatcher $catalogTitleMatcher,
        private WrestleManiaEditionResolver $wrestleManiaEditionResolver,
    ) {}

    /**
     * @param  list<YouTubePlaylistEntry>  $entries
     * @return array{
     *     links: list<YouTubeShowVideoLink>,
     *     ambiguous: list<string>,
     *     skipped: list<string>,
     *     unmatchedEntries: list<YouTubePlaylistEntry>
     * }
     */
    public function match(Promotion $promotion, array $entries, ShowType $showType = ShowType::Ppv): array
    {
        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', $showType)
            ->orderBy('date')
            ->get();

        $links = [];
        $ambiguous = [];
        $skipped = [];
        $unmatchedEntries = [];
        $matchedShowIds = [];
        $matchedVideoIds = [];

        foreach ($entries as $entry) {
            if (isset($matchedVideoIds[$entry->videoId])) {
                continue;
            }

            $parsed = $this->titleParser->parse($entry->title);

            if ($parsed === null) {
                $skipped[] = "Skipped unparseable title [{$entry->title}] (video {$entry->videoId}).";

                continue;
            }

            $inYourHouse = $this->titleParser->parseInYourHouse($parsed['eventTitle']);
            $wrestleMania = $this->wrestleManiaEditionResolver->parseStreamingTitle($entry->title);

            $candidates = match (true) {
                $inYourHouse !== null => $this->findInYourHouseCandidates($shows, $inYourHouse, $matchedShowIds),
                $wrestleMania !== null => $this->findWrestleManiaCandidates($shows, $wrestleMania['edition'], $matchedShowIds),
                default => $this->findCandidates($shows, $parsed['eventTitle'], $parsed['year'], $matchedShowIds),
            };

            if ($candidates->count() === 1) {
                $show = $candidates->first();
                $matchedShowIds[$show->id] = true;
                $matchedVideoIds[$entry->videoId] = true;
                $links[] = new YouTubeShowVideoLink($show, $entry);

                continue;
            }

            if ($candidates->count() > 1) {
                $ambiguous[] = "Ambiguous match for YouTube video [{$entry->title}] ({$entry->videoId}).";

                continue;
            }

            $unmatchedEntries[] = $entry;
        }

        return [
            'links' => $links,
            'ambiguous' => $ambiguous,
            'skipped' => $skipped,
            'unmatchedEntries' => $unmatchedEntries,
        ];
    }

    /**
     * @param  list<YouTubePlaylistEntry>  $entries
     * @return array{
     *     links: list<YouTubeShowVideoLink>,
     *     ambiguous: list<string>,
     *     skipped: list<string>,
     *     unmatchedEntries: list<YouTubePlaylistEntry>
     * }
     */
    public function matchNitro(Promotion $promotion, array $entries): array
    {
        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Tv)
            ->where('title', 'like', 'WCW Monday Nitro%')
            ->orderBy('date')
            ->get();

        $links = [];
        $ambiguous = [];
        $skipped = [];
        $unmatchedEntries = [];
        $matchedShowIds = [];
        $matchedVideoIds = [];

        foreach ($entries as $entry) {
            if (isset($matchedVideoIds[$entry->videoId])) {
                continue;
            }

            $airDate = $this->titleParser->parseNitroAirDate($entry->title);

            if ($airDate === null) {
                $skipped[] = "Skipped unparseable Nitro title [{$entry->title}] (video {$entry->videoId}).";

                continue;
            }

            $candidates = $shows->filter(function (Show $show) use ($airDate, $matchedShowIds): bool {
                if (isset($matchedShowIds[$show->id])) {
                    return false;
                }

                return $show->date->toDateString() === $airDate->toDateString();
            })->values();

            if ($candidates->count() === 1) {
                $show = $candidates->first();
                $matchedShowIds[$show->id] = true;
                $matchedVideoIds[$entry->videoId] = true;
                $links[] = new YouTubeShowVideoLink($show, $entry);

                continue;
            }

            if ($candidates->count() > 1) {
                $ambiguous[] = "Ambiguous Nitro match for YouTube video [{$entry->title}] ({$entry->videoId}).";

                continue;
            }

            $unmatchedEntries[] = $entry;
        }

        return [
            'links' => $links,
            'ambiguous' => $ambiguous,
            'skipped' => $skipped,
            'unmatchedEntries' => $unmatchedEntries,
        ];
    }

    /**
     * @param  array{number: ?int, subtitle: ?string}  $inYourHouse
     * @param  array<int, true>  $matchedShowIds
     * @return Collection<int, Show>
     */
    private function findInYourHouseCandidates(
        Collection $shows,
        array $inYourHouse,
        array $matchedShowIds,
    ): Collection {
        return $shows->filter(function (Show $show) use ($inYourHouse, $matchedShowIds): bool {
            if (isset($matchedShowIds[$show->id])) {
                return false;
            }

            $catalogParts = $this->catalogTitleMatcher->extractInYourHouseCatalogParts($show->title);

            if ($catalogParts === null) {
                return false;
            }

            if ($inYourHouse['number'] !== null) {
                return $catalogParts['number'] === $inYourHouse['number'];
            }

            if ($inYourHouse['subtitle'] === null) {
                return false;
            }

            return $this->catalogTitleMatcher->fuzzyPhraseMatches(
                $inYourHouse['subtitle'],
                $catalogParts['subtitle'],
            );
        })->values();
    }

    /**
     * @param  array<int, true>  $matchedShowIds
     * @return Collection<int, Show>
     */
    private function findWrestleManiaCandidates(
        Collection $shows,
        int $edition,
        array $matchedShowIds,
    ): Collection {
        return $shows->filter(function (Show $show) use ($edition, $matchedShowIds): bool {
            if (isset($matchedShowIds[$show->id])) {
                return false;
            }

            return $this->wrestleManiaEditionResolver->matchesEdition($edition, $show->title);
        })->values();
    }

    /**
     * @param  array<int, true>  $matchedShowIds
     * @return Collection<int, Show>
     */
    private function findCandidates(
        Collection $shows,
        string $eventTitle,
        ?int $year,
        array $matchedShowIds,
    ): Collection {
        if ($year !== null) {
            $catalogTitle = $this->titleNormalizer->normalize(
                $eventTitle,
                Carbon::createFromDate($year, 1, 1),
            );

            return $shows->filter(function (Show $show) use ($catalogTitle, $year, $matchedShowIds): bool {
                if (isset($matchedShowIds[$show->id])) {
                    return false;
                }

                return (int) $show->date->format('Y') === $year
                    && $this->catalogTitleMatcher->matches($show->title, $catalogTitle);
            })->values();
        }

        return $shows->filter(function (Show $show) use ($eventTitle, $matchedShowIds): bool {
            if (isset($matchedShowIds[$show->id])) {
                return false;
            }

            $catalogTitle = $this->titleNormalizer->normalize(
                $eventTitle,
                Carbon::parse($show->date),
            );

            return $this->catalogTitleMatcher->matches($show->title, $catalogTitle);
        })->values();
    }
}
