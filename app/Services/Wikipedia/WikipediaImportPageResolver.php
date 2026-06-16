<?php

namespace App\Services\Wikipedia;

use App\Data\ResolvedWikipediaPage;
use App\Exceptions\WikipediaImportResolutionException;
use App\Models\Show;
use App\Services\Wrestling\WrestleManiaEditionResolver;
use RuntimeException;

class WikipediaImportPageResolver
{
    public function __construct(
        private readonly WikipediaClient $client,
        private readonly WikipediaPageTitleResolver $pageTitleResolver,
        private readonly WikipediaResultsParser $resultsParser,
        private readonly WrestleManiaEditionResolver $wrestleManiaEditionResolver,
    ) {}

    /**
     * @return array{0: ResolvedWikipediaPage, 1: list<array{title: string, reason: string}>, 2: int}
     */
    public function resolve(Show $show): array
    {
        $expectedYear = $this->expectedYear($show);
        $attempts = [];

        foreach ($this->buildCandidates($show) as $candidate) {
            if (! $this->shouldTryPageTitle($show, $candidate['title'], $expectedYear, $candidate['from_search'])) {
                $attempts[] = [
                    'title' => $candidate['title'],
                    'reason' => $expectedYear === null
                        ? 'Skipped by year guard.'
                        : "Skipped: Wikipedia page year does not match show year {$expectedYear}.",
                ];

                continue;
            }

            try {
                $page = $this->client->resolvePage($candidate['title']);

                if ($expectedYear !== null && ! $this->shouldTryPageTitle($show, $page->canonicalTitle, $expectedYear, $candidate['from_search'])) {
                    $attempts[] = [
                        'title' => $candidate['title'],
                        'reason' => "Resolved to [{$page->canonicalTitle}], which does not match show year {$expectedYear}.",
                    ];

                    continue;
                }

                $parsedMatches = $this->resultsParser->parse($page->wikitext, $page->canonicalTitle, $show->title);

                return [$page, $attempts, count($parsedMatches)];
            } catch (RuntimeException $exception) {
                $attempts[] = [
                    'title' => $candidate['title'],
                    'reason' => $exception->getMessage(),
                ];
            }
        }

        throw new WikipediaImportResolutionException($show, $attempts);
    }

    /**
     * @return list<array{title: string, from_search: bool}>
     */
    private function buildCandidates(Show $show): array
    {
        $candidates = [];

        foreach ($this->pageTitleResolver->candidates($show) as $title) {
            $candidates[] = ['title' => $title, 'from_search' => false];
        }

        $show->loadMissing('promotion');

        foreach ($this->wikipediaSearchSuffixes($show) as $suffix) {
            foreach ($this->client->searchPageTitles("{$show->title} {$suffix}") as $searchTitle) {
                $candidates[] = ['title' => $searchTitle, 'from_search' => true];
            }
        }

        $unique = [];
        $deduped = [];

        foreach ($candidates as $candidate) {
            if (isset($unique[$candidate['title']])) {
                continue;
            }

            $unique[$candidate['title']] = true;
            $deduped[] = $candidate;
        }

        return $deduped;
    }

    /**
     * @return list<string>
     */
    private function wikipediaSearchSuffixes(Show $show): array
    {
        $slug = $show->promotion?->slug;

        if ($slug === null) {
            return ['WCW'];
        }

        /** @var list<string> $suffixes */
        $suffixes = config("promotions.{$slug}.wikipedia_search_suffixes", []);

        return $suffixes !== [] ? $suffixes : ['WCW'];
    }

    private function expectedYear(Show $show): ?int
    {
        if (preg_match('/\b(\d{4})$/', $show->title, $matches) === 1) {
            return (int) $matches[1];
        }

        return $show->date?->year;
    }

    private function shouldTryPageTitle(Show $show, string $pageTitle, ?int $expectedYear, bool $fromSearch): bool
    {
        if ($expectedYear === null) {
            return true;
        }

        $pageYear = $this->pageTitleYear($pageTitle);

        if ($pageYear !== null) {
            if ($pageYear !== $expectedYear) {
                return false;
            }

            return $this->pageTitleMatchesShowFamily($show->title, $pageTitle);
        }

        return ! $fromSearch || $this->pageTitleMatchesShowFamily($show->title, $pageTitle);
    }

    private function pageTitleMatchesShowFamily(string $catalogTitle, string $pageTitle): bool
    {
        if (preg_match('/^In Your House\b/i', $catalogTitle) === 1) {
            return $this->inYourHousePageMatchesCatalog($catalogTitle, $pageTitle);
        }

        if (preg_match('/^WrestleMania\b/i', $catalogTitle) === 1) {
            if (preg_match('/^WrestleMania\b/i', $pageTitle) !== 1) {
                return false;
            }

            $catalogEdition = $this->wrestleManiaEditionResolver->extractCatalogEdition($catalogTitle);
            $pageEdition = $this->wrestleManiaEditionResolver->extractCatalogEdition($pageTitle);

            if ($catalogEdition === null) {
                return true;
            }

            return $pageEdition !== null && $catalogEdition === $pageEdition;
        }

        return true;
    }

    private function inYourHousePageMatchesCatalog(string $catalogTitle, string $pageTitle): bool
    {
        if (preg_match('/^In Your House (\d+): (.+) (\d{4})$/', $catalogTitle, $catalogMatches) !== 1) {
            return preg_match('/^In Your House\b/i', $pageTitle) === 1
                || preg_match('/: In Your House\b/i', $pageTitle) === 1;
        }

        $catalogNumber = (int) $catalogMatches[1];
        $catalogSubtitle = $this->normalizeInYourHouseSubtitle($catalogMatches[2]);

        if (preg_match('/^In Your House (\d+)\b/i', $pageTitle, $pageMatches) === 1) {
            return (int) $pageMatches[1] === $catalogNumber;
        }

        if (preg_match('/^(.+): In Your House\b/i', $pageTitle, $pageMatches) === 1) {
            return $this->normalizeInYourHouseSubtitle($pageMatches[1]) === $catalogSubtitle;
        }

        return false;
    }

    private function normalizeInYourHouseSubtitle(string $subtitle): string
    {
        $subtitle = html_entity_decode(trim($subtitle), ENT_QUOTES | ENT_HTML5);
        $subtitle = str_replace(["\u{2019}", "'"], "'", $subtitle);
        $subtitle = preg_replace('/\s+/', ' ', $subtitle) ?? $subtitle;

        return strtolower($subtitle);
    }

    private function pageTitleYear(string $pageTitle): ?int
    {
        if (preg_match('/\((\d{4})\)/', $pageTitle, $matches) === 1) {
            return (int) $matches[1];
        }

        if (preg_match('/\b(\d{4})$/', $pageTitle, $matches) === 1) {
            return (int) $matches[1];
        }

        return null;
    }
}
