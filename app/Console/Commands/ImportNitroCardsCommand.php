<?php

namespace App\Console\Commands;

use App\Importers\FandomNitroImporter;
use App\Models\Promotion;
use Illuminate\Console\Command;
use RuntimeException;

class ImportNitroCardsCommand extends Command
{
    protected $signature = 'shows:import-nitro-cards
                            {--promotion=wcw : Promotion slug in our database}
                            {--from= : Only import shows on/after this date (Y-m-d)}
                            {--to= : Only import shows on/before this date (Y-m-d)}
                            {--identifier= : Limit to a single show slug or title}
                            {--dry-run : Resolve and parse without persisting}';

    protected $description = 'Import per-episode WCW Monday Nitro match cards from prowrestling.fandom.com (CC BY-SA 3.0)';

    public function handle(FandomNitroImporter $importer): int
    {
        $promotionSlug = (string) $this->option('promotion');
        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $dryRun = (bool) $this->option('dry-run');
        $from = $this->option('from') !== null ? (string) $this->option('from') : null;
        $to = $this->option('to') !== null ? (string) $this->option('to') : null;
        $identifier = $this->option('identifier') !== null ? (string) $this->option('identifier') : null;

        $this->info(($dryRun ? '[dry run] ' : '')."Importing Nitro match cards from Fandom for {$promotion->name}...");

        try {
            $result = $importer->import($promotion, $from, $to, $identifier, $dryRun);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $rows = [];

        foreach ($result['shows'] as $row) {
            $rows[] = [
                $row['show']->date?->toDateString() ?? '—',
                $row['show']->title,
                $row['status'],
                $row['matchCount'],
                $row['message'] ?? '',
            ];
        }

        if ($rows !== []) {
            $this->table(['Date', 'Show', 'Status', 'Matches', 'Notes'], $rows);
        }

        $this->table(
            ['Metric', 'Count'],
            [
                [$dryRun ? 'Shows parsed' : 'Shows imported', $result['imported']],
                ['Shows skipped', $result['skipped']],
                ['Matches'.($dryRun ? ' parsed' : ' persisted'), $result['matches']],
            ],
        );

        $this->info('Nitro match-card import complete.');

        return self::SUCCESS;
    }
}
