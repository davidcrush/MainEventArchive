<?php

namespace App\Console\Commands;

use App\Importers\FandomNitroCatalogImporter;
use App\Models\Promotion;
use Illuminate\Console\Command;
use RuntimeException;

class SeedNitroCatalogCommand extends Command
{
    protected $signature = 'shows:seed-nitro-catalog
                            {--promotion=wcw : Promotion slug in our database}
                            {--from= : Only seed episodes in/after this year}
                            {--to= : Only seed episodes in/before this year}
                            {--dry-run : Report what would be created/updated without writing}';

    protected $description = 'Seed WCW Monday Nitro show shells for every episode from the prowrestling.fandom.com episode index';

    public function handle(FandomNitroCatalogImporter $importer): int
    {
        $promotionSlug = (string) $this->option('promotion');
        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $fromYear = $this->option('from') !== null ? (int) $this->option('from') : null;
        $toYear = $this->option('to') !== null ? (int) $this->option('to') : null;

        $this->info(($dryRun ? '[dry run] ' : '')."Seeding Nitro catalog from Fandom for {$promotion->name}...");

        try {
            $result = $importer->import($promotion, $fromYear, $toYear, $dryRun);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                [$dryRun ? 'Would create' : 'Created', $result['created']],
                [$dryRun ? 'Would update' : 'Updated', $result['updated']],
                ['Episodes in scope', $result['total']],
            ],
        );

        $this->info('Nitro catalog seed complete. Run shows:import-nitro-cards to fill match cards and venues.');

        return self::SUCCESS;
    }
}
