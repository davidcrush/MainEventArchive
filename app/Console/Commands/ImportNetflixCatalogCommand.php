<?php

namespace App\Console\Commands;

use App\Data\NetflixCatalogEntry;
use App\Data\YouTubePlaylistEntry;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Services\Streaming\NetflixSavedHtmlParser;
use App\Services\Streaming\NetflixVideoSyncService;
use App\Services\YouTube\YouTubeShowMatcher;
use Illuminate\Console\Command;

class ImportNetflixCatalogCommand extends Command
{
    protected $signature = 'videos:import-netflix
                            {--promotion=wwe : Promotion slug in our database}
                            {--html= : Path to saved Netflix browse HTML (required)}
                            {--dry-run : Preview matches without writing to the database}
                            {--force : Overwrite existing Netflix URLs on matched shows}';

    protected $description = 'Import Netflix deep links from saved browse HTML into catalog videos (staff curation aid)';

    public function handle(
        NetflixSavedHtmlParser $htmlParser,
        YouTubeShowMatcher $matcher,
        NetflixVideoSyncService $syncService,
    ): int {
        $promotionSlug = (string) $this->option('promotion');
        $htmlPath = $this->option('html');
        $dryRun = (bool) $this->option('dry-run');
        $force = (bool) $this->option('force');

        if (! is_string($htmlPath) || $htmlPath === '') {
            $this->error('The --html option is required.');

            return self::FAILURE;
        }

        if (! is_readable($htmlPath)) {
            $this->error("HTML file is not readable: [{$htmlPath}]");

            return self::FAILURE;
        }

        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $catalogEntries = $htmlParser->parse(file_get_contents($htmlPath) ?: '');

        if ($catalogEntries === []) {
            $this->warn('No Netflix title links were parsed from the HTML file.');
            $this->line('Netflix search pages use jbv= title IDs (supported). Browse/collection pages use /title/ links.');
            $this->line('Save the page after results load (Ctrl+S). MHTML and HTML-only both work.');
            $this->line('For year-specific PPVs, open each series on Netflix (e.g. WWE Survivor Series) and save that page too.');

            return self::SUCCESS;
        }

        $playlistEntries = array_map(
            fn (NetflixCatalogEntry $entry): YouTubePlaylistEntry => new YouTubePlaylistEntry(
                $entry->titleId,
                $entry->title,
            ),
            $catalogEntries,
        );

        $this->info(sprintf(
            'Importing Netflix catalog for %s (%d entries)...',
            $promotion->name,
            count($catalogEntries),
        ));

        $result = $matcher->match($promotion, $playlistEntries, ShowType::Ppv);

        $created = 0;
        $updated = 0;
        $skipped = 0;

        if ($result['links'] !== []) {
            $this->newLine();
            $this->info($dryRun ? 'Matches (dry run):' : 'Linked Netflix videos:');
            $this->table(
                ['Show', 'Date', 'Netflix title', 'Title ID'],
                array_map(
                    fn ($link): array => [
                        $link->show->title,
                        $link->show->date->toDateString(),
                        $link->entry->title,
                        $link->entry->videoId,
                    ],
                    $result['links'],
                ),
            );
        }

        if (! $dryRun) {
            foreach ($result['links'] as $link) {
                $catalogEntry = new NetflixCatalogEntry(
                    titleId: $link->entry->videoId,
                    title: $link->entry->title,
                );

                $outcome = $syncService->sync($link->show, $catalogEntry, $force);

                match ($outcome) {
                    'created' => $created++,
                    'updated' => $updated++,
                    'skipped' => $skipped++,
                };
            }
        }

        foreach ($result['skipped'] as $message) {
            $this->comment($message);
        }

        foreach ($result['ambiguous'] as $warning) {
            $this->warn($warning);
        }

        if ($result['unmatchedEntries'] !== []) {
            $this->newLine();
            $this->comment('Unmatched Netflix catalog entries:');
            foreach ($result['unmatchedEntries'] as $entry) {
                $this->line("  {$entry->videoId} — {$entry->title}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Catalog entries parsed', count($catalogEntries)],
                ['Matched', count($result['links'])],
                ['Created', $dryRun ? 0 : $created],
                ['Updated', $dryRun ? 0 : $updated],
                ['Skipped (existing URL)', $dryRun ? 0 : $skipped],
                ['Ambiguous', count($result['ambiguous'])],
                ['Unmatched entries', count($result['unmatchedEntries'])],
            ],
        );

        $this->info($dryRun ? 'Dry run complete.' : 'Netflix catalog import complete.');

        return self::SUCCESS;
    }
}
