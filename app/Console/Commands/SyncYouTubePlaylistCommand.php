<?php

namespace App\Console\Commands;

use App\Data\YouTubePlaylistEntry;
use App\Enums\ShowType;
use App\Models\Promotion;
use App\Services\YouTube\YouTubePlaylistClient;
use App\Services\YouTube\YouTubeSavedHtmlParser;
use App\Services\YouTube\YouTubeShowMatcher;
use App\Services\YouTube\YouTubeVideoSyncService;
use Illuminate\Console\Command;
use RuntimeException;

class SyncYouTubePlaylistCommand extends Command
{
    protected $signature = 'videos:sync-youtube-playlist
                            {--promotion=wcw : Promotion slug in our database}
                            {--playlist=wcw_ppv : Playlist key in config/youtube.php}
                            {--source=api : Data source: api or html}
                            {--html= : Path to saved playlist HTML (required when --source=html)}
                            {--dry-run : Preview matches without writing to the database}';

    protected $description = 'Sync full-show YouTube links from a WCW playlist into catalog videos (idempotent; scheduled monthly in production)';

    public function handle(
        YouTubePlaylistClient $client,
        YouTubeSavedHtmlParser $htmlParser,
        YouTubeShowMatcher $matcher,
        YouTubeVideoSyncService $syncService,
    ): int {
        $promotionSlug = (string) $this->option('promotion');
        $playlistKey = (string) $this->option('playlist');
        $source = (string) $this->option('source');
        $dryRun = (bool) $this->option('dry-run');

        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $playlistId = config("youtube.playlists.{$playlistKey}");

        if (! is_string($playlistId) || $playlistId === '') {
            $this->error("Playlist key [{$playlistKey}] is not configured.");

            return self::FAILURE;
        }

        try {
            $includeFullEpisodes = $playlistKey === 'wcw_nitro';
            $entries = $this->collectEntries($client, $htmlParser, $source, $playlistId, $includeFullEpisodes);
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($entries === []) {
            $this->warn('No playlist entries were parsed.');

            return self::SUCCESS;
        }

        $this->info(sprintf(
            'Syncing YouTube playlist for %s (%d entries, source=%s)...',
            $promotion->name,
            count($entries),
            $source,
        ));

        $showType = in_array($playlistKey, ['wcw_clash', 'wcw_nitro'], true) ? ShowType::Tv : ShowType::Ppv;
        $result = $playlistKey === 'wcw_nitro'
            ? $matcher->matchNitro($promotion, $entries)
            : $matcher->match($promotion, $entries, $showType);

        $created = 0;
        $updated = 0;

        if ($result['links'] !== []) {
            $this->newLine();
            $this->info($dryRun ? 'Matches (dry run):' : 'Linked videos:');
            $this->table(
                ['Show', 'Date', 'YouTube title', 'Video ID'],
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
                $outcome = $syncService->sync($link->show, $link->entry);

                if ($outcome === 'created') {
                    $created++;
                } else {
                    $updated++;
                }
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
            $this->comment('Unmatched YouTube playlist entries:');
            foreach ($result['unmatchedEntries'] as $entry) {
                $this->line("  {$entry->videoId} — {$entry->title}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Playlist entries parsed', count($entries)],
                ['Matched', count($result['links'])],
                ['Created', $dryRun ? 0 : $created],
                ['Updated', $dryRun ? 0 : $updated],
                ['Skipped', count($result['skipped'])],
                ['Ambiguous', count($result['ambiguous'])],
                ['Unmatched entries', count($result['unmatchedEntries'])],
            ],
        );

        $this->info($dryRun ? 'Dry run complete.' : 'YouTube playlist sync complete.');

        return self::SUCCESS;
    }

    /**
     * @return list<YouTubePlaylistEntry>
     */
    private function collectEntries(
        YouTubePlaylistClient $client,
        YouTubeSavedHtmlParser $htmlParser,
        string $source,
        string $playlistId,
        bool $includeFullEpisodes = false,
    ): array {
        if ($source === 'html') {
            $htmlPath = $this->option('html');

            if (! is_string($htmlPath) || $htmlPath === '') {
                throw new RuntimeException('The --html option is required when --source=html.');
            }

            if (! is_readable($htmlPath)) {
                throw new RuntimeException("HTML file is not readable: [{$htmlPath}]");
            }

            return $htmlParser->parse(file_get_contents($htmlPath) ?: '', $includeFullEpisodes);
        }

        if ($source !== 'api') {
            throw new RuntimeException("Unsupported source [{$source}]. Use api or html.");
        }

        return $client->fetchPlaylistItems($playlistId, $includeFullEpisodes);
    }
}
