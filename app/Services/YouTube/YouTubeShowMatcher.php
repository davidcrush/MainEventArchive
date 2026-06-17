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
     * Match the official WWE NXT full-events YouTube playlist to NXT-titled PPV shells.
     *
     * @param  list<YouTubePlaylistEntry>  $entries
     * @return array{
     *     links: list<YouTubeShowVideoLink>,
     *     ambiguous: list<string>,
     *     skipped: list<string>,
     *     unmatchedEntries: list<YouTubePlaylistEntry>
     * }
     */
    public function matchWweNxt(Promotion $promotion, array $entries): array
    {
        $shows = Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Ppv)
            ->where('title', 'like', '%NXT%')
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

            $candidates = $this->findNxtCandidates(
                $shows,
                $parsed['eventTitle'],
                $parsed['year'],
                $matchedShowIds,
                $entry->title,
            );

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
    private function findNxtCandidates(
        Collection $shows,
        string $eventTitle,
        ?int $year,
        array $matchedShowIds,
        string $youtubeTitle = '',
    ): Collection {
        if ($year === null) {
            $brooklynEdition = $this->findNxtBrooklynEditionCandidates($shows, $eventTitle, $matchedShowIds);

            if ($brooklynEdition->isNotEmpty()) {
                return $brooklynEdition;
            }

            return $this->findCandidates($shows, $eventTitle, null, $matchedShowIds);
        }

        $catalogTitles = $this->nxtCatalogTitleCandidates($eventTitle, $year);

        $candidates = $shows->filter(function (Show $show) use ($catalogTitles, $year, $matchedShowIds): bool {
            if (isset($matchedShowIds[$show->id])) {
                return false;
            }

            if ((int) $show->date->format('Y') !== $year) {
                return false;
            }

            foreach ($catalogTitles as $catalogTitle) {
                if ($this->catalogTitleMatcher->matches($show->title, $catalogTitle)) {
                    return true;
                }
            }

            return false;
        })->values();

        if ($candidates->count() <= 1) {
            return $candidates;
        }

        return $this->resolveNxtStandAndDeliverNight($candidates, $youtubeTitle);
    }

    /**
     * @param  Collection<int, Show>  $candidates
     * @return Collection<int, Show>
     */
    private function resolveNxtStandAndDeliverNight(Collection $candidates, string $youtubeTitle): Collection
    {
        if (preg_match('/Stand\s*&\s*Deliver.*\bNight\s*1\b/i', $youtubeTitle) === 1) {
            return $candidates->sortBy('date')->take(1)->values();
        }

        if (preg_match('/Stand\s*&\s*Deliver.*\bNight\s*2\b/i', $youtubeTitle) === 1) {
            return $candidates->sortByDesc('date')->take(1)->values();
        }

        return $candidates;
    }

    /**
     * YouTube often omits the year for numbered Brooklyn TakeOvers (e.g. "Brooklyn 4" vs catalog "Brooklyn IV 2018").
     *
     * @param  array<int, true>  $matchedShowIds
     * @return Collection<int, Show>
     */
    private function findNxtBrooklynEditionCandidates(
        Collection $shows,
        string $eventTitle,
        array $matchedShowIds,
    ): Collection {
        if (preg_match('/Brooklyn\s+([1-4])$/i', $eventTitle, $matches) !== 1) {
            return collect();
        }

        $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV'][$matches[1]] ?? null;

        if ($roman === null) {
            return collect();
        }

        return $shows->filter(function (Show $show) use ($roman, $matchedShowIds): bool {
            if (isset($matchedShowIds[$show->id])) {
                return false;
            }

            return preg_match('/Brooklyn\s+'.$roman.'\b/i', $show->title) === 1;
        })->values();
    }

    /**
     * @return list<string>
     */
    private function nxtCatalogTitleCandidates(string $eventTitle, int $year): array
    {
        $date = Carbon::createFromDate($year, 1, 1);
        $candidates = [];

        $addCandidate = function (string $title) use (&$candidates, $date): void {
            $candidates[] = $this->titleNormalizer->normalize($title, $date);
        };

        $addCandidate($eventTitle);

        $title = preg_replace('/^(?:NXT UK\s+)?NXT\s+/i', 'NXT ', trim($eventTitle)) ?? trim($eventTitle);

        if (preg_match('/^NXT TakeOver:\s*(.+)$/i', $title, $matches) === 1) {
            $addCandidate('NXT '.trim($matches[1]));
        }

        if (preg_match('/^NXT TakeOver\s+(.+)$/i', $title, $matches) === 1) {
            $addCandidate('NXT TakeOver: '.trim($matches[1]));
        }

        if (preg_match('/^NXT (?!TakeOver\b)(.+)$/i', $title, $matches) === 1) {
            $addCandidate('NXT TakeOver: '.trim($matches[1]));
        }

        if (preg_match('/^NXT Great American Bash\b/i', $title) === 1) {
            $addCandidate((string) preg_replace(
                '/^NXT Great American Bash/i',
                'NXT The Great American Bash',
                $title,
            ));
        }

        if (preg_match('/^NXT TakeOver:\s*Brooklyn\s+([1-4])$/i', $title, $matches) === 1) {
            $roman = ['1' => 'I', '2' => 'II', '3' => 'III', '4' => 'IV'][$matches[1]] ?? $matches[1];
            $addCandidate("NXT TakeOver: Brooklyn {$roman}");
        }

        return array_values(array_unique($candidates));
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
