<?php

namespace App\Console\Commands;

use App\Models\Promotion;
use App\Services\Wikipedia\WikipediaNitroMetadataImporter;
use Illuminate\Console\Command;

class ImportNitroMetadataCommand extends Command
{
    protected $signature = 'shows:import-nitro-metadata
                            {--promotion=wcw : Promotion slug in our database}';

    protected $description = 'Enrich Nitro catalog shows with Nielsen ratings and venue data from Wikipedia notable episodes table';

    public function handle(WikipediaNitroMetadataImporter $importer): int
    {
        $promotionSlug = (string) $this->option('promotion');
        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $this->info("Enriching Nitro metadata from Wikipedia for {$promotion->name}...");

        try {
            $result = $importer->import($promotion);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Shows updated', $result['updated']],
                ['Skipped (no catalog match)', $result['skipped']],
            ],
        );

        foreach ($result['warnings'] as $warning) {
            $this->warn($warning);
        }

        $this->info('Nitro metadata import complete.');

        return self::SUCCESS;
    }
}
