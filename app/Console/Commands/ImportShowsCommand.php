<?php

namespace App\Console\Commands;

use App\Console\Concerns\RunsParallelShowProcessing;
use App\Contracts\ShowDataImporter;
use App\Data\ImportRequest;
use App\Importers\WikidataShowImporter;
use App\Importers\WikipediaShowImporter;
use App\Models\Show;
use Illuminate\Console\Command;

class ImportShowsCommand extends Command
{
    use RunsParallelShowProcessing;

    protected $signature = 'shows:import
                            {source=wikidata : Import source (wikidata, wikipedia)}
                            {identifier? : Wikidata QID, Wikipedia page title, or show slug}
                            {--from=1993 : Start year (inclusive)}
                            {--to=1996 : End year (inclusive)}
                            {--promotion= : Promotion slug filter (required for wikipedia bulk import)}
                            {--strict : Exit with failure when any show is skipped or unresolved}
                            {--workers=1 : Number of concurrent worker processes (wikipedia bulk import only)}
                            {--chunk= : Internal: worker slice index}
                            {--of= : Internal: total worker count}
                            {--json : Internal: emit machine-readable results}';

    protected $description = 'Import wrestling shows from external data sources';

    public function handle(): int
    {
        $source = $this->argument('source');

        if (! in_array($source, ['wikidata', 'wikipedia'], true)) {
            $this->error("Unsupported source [{$source}]. Supported: wikidata, wikipedia");

            return self::FAILURE;
        }

        $fromYear = (int) $this->option('from');
        $toYear = (int) $this->option('to');

        if ($fromYear > $toYear) {
            $this->error('--from year must be less than or equal to --to year.');

            return self::FAILURE;
        }

        $identifier = $this->argument('identifier');
        $promotionSlug = $this->option('promotion');

        if ($source === 'wikipedia' && $identifier === null && blank($promotionSlug)) {
            $this->error('Bulk Wikipedia import requires --promotion (e.g. --promotion=wcw or --promotion=wwe).');

            return self::FAILURE;
        }

        if ($source === 'wikipedia' && $identifier === null && ! $this->isParallelWorker()) {
            $workers = $this->resolveWorkerCount(min(
                (int) $this->option('workers'),
                max(1, $this->wikipediaShowCount($promotionSlug, $fromYear, $toYear)),
            ));

            if ($workers > 1) {
                return $this->runParallelImport(
                    is_string($promotionSlug) ? $promotionSlug : null,
                    $fromYear,
                    $toYear,
                    $workers,
                );
            }
        }

        $emitJson = (bool) $this->option('json');

        if ($source === 'wikipedia') {
            if (! $emitJson) {
                $promotionLabel = filled($promotionSlug) ? strtoupper((string) $promotionSlug) : 'all promotions';
                $this->info($identifier
                    ? "Enriching show card from Wikipedia page [{$identifier}]..."
                    : "Enriching {$promotionLabel} show cards from Wikipedia ({$fromYear}-{$toYear})...");
            }
            $importer = app(WikipediaShowImporter::class);
        } else {
            if (! $emitJson) {
                $this->info("Importing WCW shows from Wikidata ({$fromYear}-{$toYear})...");
            }
            $importer = app(WikidataShowImporter::class);
        }

        /** @var ShowDataImporter $importer */
        $result = $importer->import(new ImportRequest(
            source: $source,
            fromYear: $fromYear,
            toYear: $toYear,
            identifier: $identifier,
            promotionSlug: filled($promotionSlug) ? (string) $promotionSlug : null,
            chunkIndex: $this->isParallelWorker() ? (int) $this->option('chunk') : null,
            chunkTotal: $this->isParallelWorker() ? (int) $this->option('of') : null,
        ));

        if ($emitJson) {
            $this->emitJsonLine([
                'created' => $result->created,
                'updated' => $result->updated,
                'skipped' => $result->skipped,
                'warnings' => $result->warnings,
            ]);

            return $source === 'wikipedia' && $this->option('strict') && $result->skipped > 0
                ? self::FAILURE
                : self::SUCCESS;
        }

        $this->renderImportSummary(
            $source,
            $result->created,
            $result->updated,
            $result->skipped,
            $result->warnings,
        );

        if ($source === 'wikipedia' && $this->option('strict') && $result->skipped > 0) {
            $this->error('Wikipedia import finished with unresolved shows. Re-run with shows:verify-wikipedia for details.');

            return self::FAILURE;
        }

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    private function runParallelImport(?string $promotionSlug, int $fromYear, int $toYear, int $workers): int
    {
        $promotionLabel = $promotionSlug !== null ? strtoupper($promotionSlug) : 'all promotions';
        $this->info(sprintf(
            'Enriching %s show cards from Wikipedia (%d-%d) across %d parallel worker(s)...',
            $promotionLabel,
            $fromYear,
            $toYear,
            $workers,
        ));

        $arguments = array_values(array_filter([
            'wikipedia',
            $promotionSlug !== null ? "--promotion={$promotionSlug}" : null,
            "--from={$fromYear}",
            "--to={$toYear}",
            $this->option('strict') ? '--strict' : null,
        ]));

        $results = $this->runWorkerPool('shows:import', $arguments, $workers);

        $created = 0;
        $updated = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($results as $result) {
            foreach ($this->decodeWorkerJsonLines($result->output()) as $record) {
                $created += (int) ($record['created'] ?? 0);
                $updated += (int) ($record['updated'] ?? 0);
                $skipped += (int) ($record['skipped'] ?? 0);

                foreach ((array) ($record['warnings'] ?? []) as $warning) {
                    $warnings[] = (string) $warning;
                }
            }

            if (! $result->successful() && trim($result->errorOutput()) !== '') {
                $this->warn(trim($result->errorOutput()));
            }
        }

        $this->renderImportSummary('wikipedia', $created, $updated, $skipped, $warnings);

        if ($this->option('strict') && $skipped > 0) {
            $this->error('Wikipedia import finished with unresolved shows. Re-run with shows:verify-wikipedia for details.');

            return self::FAILURE;
        }

        $this->info('Import complete.');

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $warnings
     */
    private function renderImportSummary(string $source, int $created, int $updated, int $skipped, array $warnings): void
    {
        $this->table(
            ['Metric', 'Count'],
            [
                [$source === 'wikipedia' ? 'Matches imported' : 'Shows created', $created],
                ['Shows updated', $updated],
                ['Skipped', $skipped],
            ],
        );

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }
    }

    private function wikipediaShowCount(?string $promotionSlug, int $fromYear, int $toYear): int
    {
        return Show::query()
            ->when(filled($promotionSlug), fn ($query) => $query->whereHas(
                'promotion',
                fn ($promotionQuery) => $promotionQuery->where('slug', $promotionSlug),
            ))
            ->whereBetween('date', [
                "{$fromYear}-01-01",
                "{$toYear}-12-31",
            ])
            ->count();
    }
}
