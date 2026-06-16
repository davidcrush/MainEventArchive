<?php

namespace App\Services\Wikipedia;

use App\Data\ResolvedWikipediaPage;
use App\Exceptions\WikipediaImportResolutionException;
use App\Models\Show;
use RuntimeException;

class WikipediaImportPageResolver
{
    public function __construct(
        private readonly WikipediaClient $client,
        private readonly WikipediaPageTitleResolver $pageTitleResolver,
        private readonly WikipediaResultsParser $resultsParser,
    ) {}

    /**
     * @return array{0: ResolvedWikipediaPage, 1: list<array{title: string, reason: string}>}
     */
    public function resolve(Show $show): array
    {
        $expectedYear = $this->expectedYear($show);
        $attempts = [];

        foreach ($this->buildCandidates($show) as $candidate) {
            if (! $this->shouldTryPageTitle($candidate['title'], $expectedYear, $candidate['from_search'])) {
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

                if ($expectedYear !== null && ! $this->shouldTryPageTitle($page->canonicalTitle, $expectedYear, $candidate['from_search'])) {
                    $attempts[] = [
                        'title' => $candidate['title'],
                        'reason' => "Resolved to [{$page->canonicalTitle}], which does not match show year {$expectedYear}.",
                    ];

                    continue;
                }

                $this->resultsParser->parse($page->wikitext, $page->canonicalTitle, $show->title);

                return [$page, $attempts];
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

    private function shouldTryPageTitle(string $pageTitle, ?int $expectedYear, bool $fromSearch): bool
    {
        if ($expectedYear === null) {
            return true;
        }

        $pageYear = $this->pageTitleYear($pageTitle);

        if ($pageYear !== null) {
            return $pageYear === $expectedYear;
        }

        return ! $fromSearch;
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
