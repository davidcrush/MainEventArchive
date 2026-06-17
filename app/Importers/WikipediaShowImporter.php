<?php

namespace App\Importers;

use App\Contracts\ShowDataImporter;
use App\Data\ImportRequest;
use App\Data\ImportResult;
use App\Data\ResolvedWikipediaPage;
use App\Exceptions\WikipediaImportResolutionException;
use App\Importers\Concerns\PersistsParsedMatches;
use App\Models\Show;
use App\Models\Venue;
use App\Services\Wikipedia\WikipediaImportPageResolver;
use App\Services\Wikipedia\WikipediaInfoboxParser;
use App\Services\Wikipedia\WikipediaPageTitleResolver;
use App\Services\Wikipedia\WikipediaResultsParser;
use Illuminate\Support\Collection;
use RuntimeException;

class WikipediaShowImporter implements ShowDataImporter
{
    use PersistsParsedMatches;

    public function __construct(
        private readonly WikipediaImportPageResolver $pageResolver,
        private readonly WikipediaPageTitleResolver $pageTitleResolver,
        private readonly WikipediaInfoboxParser $infoboxParser,
        private readonly WikipediaResultsParser $resultsParser,
        private readonly WikipediaVenueImporter $venueImporter,
    ) {}

    public function import(ImportRequest $request): ImportResult
    {
        $shows = $this->resolveShows($request);

        if ($shows->isEmpty()) {
            return new ImportResult(warnings: ['No shows found to enrich from Wikipedia.']);
        }

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($shows as $show) {
            try {
                [$page, $attempts, $declaredMatchCount] = $this->pageResolver->resolve($show);
                $parsedMatches = $this->resultsParser->parse($page->wikitext, $page->canonicalTitle, $show->title);
                $metadata = $this->infoboxParser->parse($page->wikitext, $page->canonicalTitle, $show->title);
                $importedMatchCount = $this->persistMatches($show, $parsedMatches);

                if ($importedMatchCount !== $declaredMatchCount) {
                    throw new RuntimeException("Imported {$importedMatchCount} matches but Wikipedia declares {$declaredMatchCount} for [{$show->title}].");
                }

                $updates = [
                    'source' => $show->source === 'manual' ? 'wikipedia' : $show->source,
                    'source_url' => 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $page->canonicalTitle),
                    'imported_at' => now(),
                ];

                if ($metadata->venue !== null) {
                    $updates['venue'] = $metadata->venue;
                }

                if ($metadata->city !== null) {
                    $updates['city'] = $metadata->city;
                }

                if ($metadata->attendance !== null) {
                    $updates['attendance'] = $metadata->attendance;
                }

                if (count($metadata->venueLinks) === 1) {
                    try {
                        $venueLink = $metadata->venueLinks[0];
                        $alias = $venueLink->displayName !== $venueLink->pageTitle
                            ? $venueLink->displayName
                            : null;
                        $venue = $this->venueImporter->importFromPageTitle(
                            $venueLink->pageTitle,
                            $alias,
                        );
                        $updates['venue_id'] = $venue->id;
                    } catch (RuntimeException $venueException) {
                        $warnings[] = "{$show->title} venue: {$venueException->getMessage()}";
                    }
                }

                $show->update($updates);

                $updated++;
                $created += $importedMatchCount;
            } catch (WikipediaImportResolutionException $exception) {
                $warnings[] = $exception->getMessage();
                $skipped++;
            } catch (RuntimeException $exception) {
                $warnings[] = "{$show->title} ({$show->slug}): {$exception->getMessage()}";
                $skipped++;
            }
        }

        return new ImportResult($created, $updated, $skipped, $warnings);
    }

    public function fetchWikitextForShow(Show $show): string
    {
        [$page] = $this->pageResolver->resolve($show);

        return $page->wikitext;
    }

    /**
     * @return array{0: ResolvedWikipediaPage, 1: list<array{title: string, reason: string}>, 2: int}
     */
    public function resolvePageForShow(Show $show): array
    {
        return $this->pageResolver->resolve($show);
    }

    public function linkVenueFromWikitext(Show $show, string $wikitext, bool $refreshVenue = false): ?Venue
    {
        $metadata = $this->infoboxParser->parse(
            $wikitext,
            $this->pageTitleResolver->resolve($show),
            $show->title,
        );

        if (count($metadata->venueLinks) !== 1) {
            return null;
        }

        $venueLink = $metadata->venueLinks[0];
        $alias = $venueLink->displayName !== $venueLink->pageTitle
            ? $venueLink->displayName
            : null;

        $venue = $this->venueImporter->importFromPageTitle(
            $venueLink->pageTitle,
            $alias,
            $refreshVenue,
        );

        $show->update(['venue_id' => $venue->id]);

        return $venue;
    }

    private function resolveShows(ImportRequest $request)
    {
        if ($request->identifier !== null && $request->identifier !== '') {
            $identifier = $request->identifier;

            $show = Show::query()
                ->where('slug', $identifier)
                ->orWhere('title', $identifier)
                ->first();

            if ($show === null) {
                $pageTitle = $this->pageTitleResolver->resolve(
                    new Show(['title' => str_replace('_', ' ', $identifier)]),
                    $identifier,
                );

                if (preg_match('/^(.+?) \((\d{4})\)$/', $pageTitle, $matches) === 1) {
                    $derivedTitle = $matches[1].' '.$matches[2];
                    $show = Show::query()->where('title', $derivedTitle)->first();
                }
            }

            if ($show !== null) {
                return collect([$show]);
            }

            return collect();
        }

        $shows = Show::query()
            ->when($request->promotionSlug !== null, fn ($query) => $query->whereHas(
                'promotion',
                fn ($promotionQuery) => $promotionQuery->where('slug', $request->promotionSlug),
            ))
            ->whereBetween('date', [
                "{$request->fromYear}-01-01",
                "{$request->toYear}-12-31",
            ])
            ->orderBy('date')
            ->get();

        return $this->applyChunk($shows, $request);
    }

    /**
     * Reduce the ordered show list to a single round-robin worker slice.
     *
     * @param  Collection<int, Show>  $shows
     * @return Collection<int, Show>
     */
    private function applyChunk($shows, ImportRequest $request)
    {
        if ($request->chunkTotal === null || $request->chunkTotal <= 1) {
            return $shows;
        }

        $total = $request->chunkTotal;
        $index = $request->chunkIndex ?? 0;

        return $shows
            ->values()
            ->filter(static fn ($show, int $position): bool => $position % $total === $index)
            ->values();
    }
}
