<?php

namespace App\Console\Commands;

use App\Contracts\ShowDataImporter;
use App\Data\ImportRequest;
use App\Importers\WikidataShowImporter;
use App\Importers\WikipediaShowImporter;
use Illuminate\Console\Command;

class ImportShowsCommand extends Command
{
    protected $signature = 'shows:import
                            {source=wikidata : Import source (wikidata, wikipedia)}
                            {identifier? : Wikidata QID, Wikipedia page title, or show slug}
                            {--from=1993 : Start year (inclusive)}
                            {--to=1996 : End year (inclusive)}
                            {--promotion= : Promotion slug filter (required for wikipedia bulk import)}
                            {--strict : Exit with failure when any show is skipped or unresolved}';

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

        if ($source === 'wikipedia') {
            $promotionLabel = filled($promotionSlug) ? strtoupper((string) $promotionSlug) : 'all promotions';
            $this->info($identifier
                ? "Enriching show card from Wikipedia page [{$identifier}]..."
                : "Enriching {$promotionLabel} show cards from Wikipedia ({$fromYear}-{$toYear})...");
            $importer = app(WikipediaShowImporter::class);
        } else {
            $this->info("Importing WCW shows from Wikidata ({$fromYear}-{$toYear})...");
            $importer = app(WikidataShowImporter::class);
        }

        /** @var ShowDataImporter $importer */
        $result = $importer->import(new ImportRequest(
            source: $source,
            fromYear: $fromYear,
            toYear: $toYear,
            identifier: $identifier,
            promotionSlug: filled($promotionSlug) ? (string) $promotionSlug : null,
        ));

        $this->table(
            ['Metric', 'Count'],
            [
                [$source === 'wikipedia' ? 'Matches imported' : 'Shows created', $result->created],
                ['Shows updated', $result->updated],
                ['Skipped', $result->skipped],
            ],
        );

        foreach ($result->warnings as $warning) {
            $this->warn($warning);
        }

        if ($source === 'wikipedia' && $this->option('strict') && $result->skipped > 0) {
            $this->error('Wikipedia import finished with unresolved shows. Re-run with shows:verify-wikipedia for details.');

            return self::FAILURE;
        }

        $this->info('Import complete.');

        return self::SUCCESS;
    }
}
