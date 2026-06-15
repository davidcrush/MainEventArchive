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
                            {--to=1996 : End year (inclusive)}';

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

        if ($source === 'wikipedia') {
            $this->info($identifier
                ? "Enriching show card from Wikipedia page [{$identifier}]..."
                : "Enriching show cards from Wikipedia ({$fromYear}-{$toYear})...");
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

        $this->info('Import complete.');

        return self::SUCCESS;
    }
}
