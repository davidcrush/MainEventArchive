<?php

namespace App\Importers;

use App\Contracts\ShowDataImporter;
use App\Data\ImportRequest;
use App\Data\ImportResult;
use App\Enums\ShowStatus;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\ShowSlugGenerator;
use Carbon\Carbon;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class WikidataShowImporter implements ShowDataImporter
{
    public function __construct(
        private readonly ShowSlugGenerator $slugGenerator,
    ) {}

    public function import(ImportRequest $request): ImportResult
    {
        $promotion = Promotion::query()->where('slug', 'wcw')->first();

        if ($promotion === null) {
            $promotion = Promotion::query()->create([
                'name' => 'World Championship Wrestling',
                'slug' => 'wcw',
            ]);
        }

        $events = $this->fetchEvents($request->fromYear, $request->toYear);
        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($events as $event) {
            $sourceId = $this->extractQid($event['event']);

            if ($sourceId === null) {
                $warnings[] = 'Skipped event with missing QID.';
                $skipped++;

                continue;
            }

            $existing = Show::query()->where('source', 'wikidata')->where('source_id', $sourceId)->first();

            if ($existing !== null && $request->identifier !== null) {
                $skipped++;

                continue;
            }

            $title = $event['eventLabel'] ?? 'Untitled Event';
            $date = Carbon::parse($event['date'])->startOfDay();
            $slug = $this->slugGenerator->generate($title, $date, $existing?->id);

            $attributes = [
                'promotion_id' => $promotion->id,
                'title' => $title,
                'slug' => $slug,
                'date' => $date->toDateString(),
                'venue' => $event['venueLabel'] ?? null,
                'city' => $event['cityLabel'] ?? null,
                'show_type' => ShowType::Ppv,
                'status' => ShowStatus::PendingReview,
                'source' => 'wikidata',
                'source_id' => $sourceId,
                'source_url' => "https://www.wikidata.org/wiki/{$sourceId}",
                'imported_at' => now(),
            ];

            if ($existing !== null) {
                $existing->update($attributes);
                $updated++;
            } else {
                Show::query()->create($attributes);
                $created++;
            }
        }

        if ($events === []) {
            $warnings[] = "No Wikidata events found for WCW PPVs {$request->fromYear}-{$request->toYear}.";
        }

        return new ImportResult($created, $updated, $skipped, $warnings);
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchEvents(int $fromYear, int $toYear): array
    {
        $wcw = config('wikidata.entities.wcw');
        $from = "{$fromYear}-01-01T00:00:00Z";
        $to = "{$toYear}-12-31T23:59:59Z";

        $query = <<<SPARQL
SELECT DISTINCT ?event ?eventLabel ?date ?venueLabel ?cityLabel WHERE {
  {
    ?event wdt:P31/wdt:P279* wd:Q17361156 .
    { ?event wdt:P664 wd:{$wcw} . }
    UNION
    { ?event wdt:P449 wd:{$wcw} . }
    UNION
    { ?event wdt:P1953 wd:{$wcw} . }
  }
  UNION
  {
    ?event wdt:P31/wdt:P279* wd:Q18608583 .
    ?event wdt:P449 wd:{$wcw} .
  }
  ?event wdt:P585 ?date .
  FILTER(?date >= "{$from}"^^xsd:dateTime && ?date <= "{$to}"^^xsd:dateTime)
  OPTIONAL { ?event wdt:P276 ?venue . ?venue rdfs:label ?venueLabel . FILTER(LANG(?venueLabel) = "en") }
  OPTIONAL { ?event wdt:P131 ?city . ?city rdfs:label ?cityLabel . FILTER(LANG(?cityLabel) = "en") }
  SERVICE wikibase:label { bd:serviceParam wikibase:language "en". }
}
ORDER BY ?date
SPARQL;

        $response = $this->client()->get(config('wikidata.query_endpoint'), [
            'query' => $query,
            'format' => 'json',
        ]);

        if (! $response->successful()) {
            throw new \RuntimeException('Wikidata SPARQL query failed: '.$response->status());
        }

        $bindings = $response->json('results.bindings', []);

        return array_map(function (array $row): array {
            return [
                'event' => $row['event']['value'] ?? null,
                'eventLabel' => $row['eventLabel']['value'] ?? null,
                'date' => $row['date']['value'] ?? null,
                'venueLabel' => $row['venueLabel']['value'] ?? null,
                'cityLabel' => $row['cityLabel']['value'] ?? null,
            ];
        }, $bindings);
    }

    private function client(): PendingRequest
    {
        return Http::timeout(60)
            ->withHeaders([
                'Accept' => 'application/sparql-results+json',
                'User-Agent' => 'MainEventArchive/1.0 (https://github.com/main-event-archive)',
            ]);
    }

    private function extractQid(?string $uri): ?string
    {
        if ($uri === null) {
            return null;
        }

        return Str::afterLast($uri, '/');
    }
}
