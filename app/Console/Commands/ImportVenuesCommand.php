<?php

namespace App\Console\Commands;

use App\Importers\WikipediaShowImporter;
use App\Models\Show;
use Illuminate\Console\Command;
use RuntimeException;

class ImportVenuesCommand extends Command
{
    protected $signature = 'shows:import-venues
                            {--from=1993 : Start year inclusive}
                            {--to=2001 : End year inclusive}
                            {--promotion= : Promotion slug filter}
                            {--slug= : Optional single show slug}
                            {--force : Re-fetch Wikipedia metadata for venues already linked}';

    protected $description = 'Link shows to venue records via Wikipedia venue pages';

    public function handle(WikipediaShowImporter $importer): int
    {
        $fromYear = (int) $this->option('from');
        $toYear = (int) $this->option('to');
        $slug = $this->option('slug');
        $force = (bool) $this->option('force');

        if ($fromYear > $toYear) {
            $this->error('--from year must be less than or equal to --to year.');

            return self::FAILURE;
        }

        $shows = Show::query()
            ->whereBetween('date', ["{$fromYear}-01-01", "{$toYear}-12-31"])
            ->when(filled($this->option('promotion')), fn ($query) => $query->whereHas(
                'promotion',
                fn ($promotionQuery) => $promotionQuery->where('slug', $this->option('promotion')),
            ))
            ->when(! $force, fn ($query) => $query->whereNull('venue_id'))
            ->when(is_string($slug) && $slug !== '', fn ($query) => $query->where('slug', $slug))
            ->whereNotNull('venue')
            ->orderBy('date')
            ->get();

        if ($shows->isEmpty()) {
            $this->warn('No shows found to link venues.');

            return self::SUCCESS;
        }

        $this->info("Linking venues from Wikipedia ({$fromYear}-{$toYear})...");

        $linked = 0;
        $skipped = 0;
        $warnings = [];

        foreach ($shows as $show) {
            try {
                $wikitext = $importer->fetchWikitextForShow($show);
                $venue = $importer->linkVenueFromWikitext($show, $wikitext, $force);

                if ($venue === null) {
                    $skipped++;
                    $warnings[] = "{$show->title}: skipped (multi-venue or no venue wikilink).";

                    continue;
                }

                $linked++;
                $this->line("  {$show->title} → {$venue->name}");
            } catch (RuntimeException $exception) {
                $skipped++;
                $warnings[] = "{$show->title}: {$exception->getMessage()}";
            }
        }

        $this->table(
            ['Metric', 'Count'],
            [
                ['Venues linked', $linked],
                ['Skipped', $skipped],
            ],
        );

        foreach ($warnings as $warning) {
            $this->warn($warning);
        }

        $this->info('Venue import complete.');

        return self::SUCCESS;
    }
}
