<?php

namespace App\Importers;

use App\Contracts\ShowDataImporter;
use App\Data\ImportRequest;
use App\Data\ImportResult;
use App\Data\ParsedWikipediaMatch;
use App\Models\MatchParticipant;
use App\Models\Show;
use App\Models\Venue;
use App\Models\WrestlingMatch;
use App\Services\Wikipedia\WikipediaClient;
use App\Services\Wikipedia\WikipediaInfoboxParser;
use App\Services\Wikipedia\WikipediaPageTitleResolver;
use App\Services\Wikipedia\WikipediaResultsParser;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class WikipediaShowImporter implements ShowDataImporter
{
    public function __construct(
        private readonly WikipediaClient $client,
        private readonly WikipediaPageTitleResolver $pageTitleResolver,
        private readonly WikipediaResultsParser $resultsParser,
        private readonly WikipediaInfoboxParser $infoboxParser,
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
                [$pageTitle, $wikitext] = $this->resolvePageAndWikitext($show);
                $parsedMatches = $this->resultsParser->parse($wikitext, $pageTitle, $show->title);
                $metadata = $this->infoboxParser->parse($wikitext, $pageTitle, $show->title);
                $matchCount = $this->persistMatches($show, $parsedMatches, $pageTitle);

                $updates = [
                    'source' => $show->source === 'manual' ? 'wikipedia' : $show->source,
                    'source_url' => 'https://en.wikipedia.org/wiki/'.str_replace(' ', '_', $pageTitle),
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
                $created += $matchCount;
            } catch (RuntimeException $exception) {
                $warnings[] = "{$show->title}: {$exception->getMessage()}";
                $skipped++;
            }
        }

        return new ImportResult($created, $updated, $skipped, $warnings);
    }

    public function fetchWikitextForShow(Show $show): string
    {
        [, $wikitext] = $this->resolvePageAndWikitext($show);

        return $wikitext;
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

    /**
     * @param  list<ParsedWikipediaMatch>  $parsedMatches
     */
    private function persistMatches(Show $show, array $parsedMatches, string $pageTitle): int
    {
        return DB::transaction(function () use ($show, $parsedMatches): int {
            $show->matches()->each(function (WrestlingMatch $match): void {
                $match->participants()->delete();
                $match->delete();
            });

            foreach ($parsedMatches as $parsedMatch) {
                $match = WrestlingMatch::query()->create([
                    'show_id' => $show->id,
                    'card_order' => $parsedMatch->cardOrder,
                    'match_type' => $parsedMatch->matchType,
                    'title_name' => $parsedMatch->titleName,
                    'is_rateable' => $parsedMatch->isRateable,
                    'is_ppv' => $parsedMatch->isPpv,
                    'winner_side' => $parsedMatch->winnerSide,
                    'finish' => $parsedMatch->finish,
                    'duration_seconds' => $parsedMatch->durationSeconds,
                ]);

                foreach ($parsedMatch->participants as $participant) {
                    MatchParticipant::query()->create([
                        'match_id' => $match->id,
                        'name' => $participant['name'],
                        'side' => $participant['side'],
                        'sort_order' => $participant['sort_order'],
                    ]);
                }
            }

            return count($parsedMatches);
        });
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

        return Show::query()
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
    }

    /**
     * @return list<string>
     */
    private function wikipediaSearchSuffixes(Show $show): array
    {
        $show->loadMissing('promotion');

        $slug = $show->promotion?->slug;

        if ($slug === null) {
            return ['WCW'];
        }

        /** @var list<string> $suffixes */
        $suffixes = config("promotions.{$slug}.wikipedia_search_suffixes", []);

        return $suffixes !== [] ? $suffixes : ['WCW'];
    }

    /**
     * @return array{0: string, 1: string}
     */
    private function resolvePageAndWikitext(Show $show): array
    {
        $candidates = $this->pageTitleResolver->candidates($show);

        foreach ($this->wikipediaSearchSuffixes($show) as $suffix) {
            foreach ($this->client->searchPageTitles("{$show->title} {$suffix}") as $searchTitle) {
                if (! in_array($searchTitle, $candidates, true)) {
                    $candidates[] = $searchTitle;
                }
            }
        }

        $lastException = null;

        foreach (array_unique($candidates) as $pageTitle) {
            try {
                $wikitext = $this->client->fetchWikitext($pageTitle);
                $this->resultsParser->parse($wikitext, $pageTitle, $show->title);

                return [$pageTitle, $wikitext];
            } catch (RuntimeException $exception) {
                $lastException = $exception;
            }
        }

        throw $lastException ?? new RuntimeException("No Wikipedia page with parseable results found for {$show->title}.");
    }
}
