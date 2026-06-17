<?php

namespace App\Console\Commands;

use App\Importers\WwePpvCatalogImporter;
use Illuminate\Console\Command;

class SeedWwePpvCatalogCommand extends Command
{
    protected $signature = 'shows:seed-wwe-ppv-catalog
                            {--from=1996 : Start year inclusive}
                            {--to=2001 : End year inclusive}
                            {--dry-run : Report what would be created/updated/deleted without writing}';

    protected $description = 'Seed WWE PPV show shells from bundled Cagematch listing saves';

    public function handle(WwePpvCatalogImporter $importer): int
    {
        $fromYear = (int) $this->option('from');
        $toYear = (int) $this->option('to');

        if ($fromYear > $toYear) {
            $this->error('--from year must be less than or equal to --to year.');

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $promotion = $importer->ensurePromotion();

        $this->info(($dryRun ? '[dry run] ' : '')."Seeding WWE PPV catalog for {$promotion->name} ({$fromYear}-{$toYear})...");

        $result = $importer->import($promotion, $fromYear, $toYear, $dryRun);

        $this->table(
            ['Metric', 'Count'],
            [
                [$dryRun ? 'Would create' : 'Created', $result['created']],
                [$dryRun ? 'Would update' : 'Updated', $result['updated']],
                [$dryRun ? 'Would delete' : 'Deleted', $result['deleted']],
                ['PPVs in scope', $result['total']],
            ],
        );

        $this->info('WWE PPV catalog seed complete. Run shows:import wikipedia --promotion=wwe to fill match cards and venues.');

        return self::SUCCESS;
    }
}
