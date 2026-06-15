<?php

namespace App\Console\Commands;

use App\Data\CagematchEvent;
use App\Models\Promotion;
use App\Services\Cagematch\CagematchClient;
use App\Services\Cagematch\CagematchListingParser;
use App\Services\Cagematch\CagematchShowMatcher;
use Illuminate\Console\Command;

class LinkCagematchShowsCommand extends Command
{
    protected $signature = 'shows:link-cagematch
                            {--promotion=wcw : Promotion slug in our database}
                            {--from=1993 : Start year inclusive}
                            {--to=1996 : End year inclusive}
                            {--slug= : Optional single show slug}
                            {--html=* : Saved Cagematch listing HTML file path(s) from your browser}
                            {--html-dir= : Directory of saved listing HTML files (sorted by name)}
                            {--dry-run : Preview matches without saving}';

    protected $description = 'Link catalog shows to Cagematch event pages (staff curation; URL discovery only)';

    public function handle(
        CagematchClient $client,
        CagematchListingParser $parser,
        CagematchShowMatcher $matcher,
    ): int {
        $promotionSlug = (string) $this->option('promotion');
        $fromYear = (int) $this->option('from');
        $toYear = (int) $this->option('to');
        $slug = $this->option('slug');
        $dryRun = (bool) $this->option('dry-run');

        if ($fromYear > $toYear) {
            $this->error('--from year must be less than or equal to --to year.');

            return self::FAILURE;
        }

        try {
            $client->promotionConfig($promotionSlug);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $promotion = Promotion::query()->where('slug', $promotionSlug)->first();

        if ($promotion === null) {
            $this->error("Promotion [{$promotionSlug}] was not found in the database.");

            return self::FAILURE;
        }

        $this->info("Linking Cagematch URLs for {$promotion->name} ({$fromYear}-{$toYear})...");

        try {
            $events = $this->collectEvents($client, $parser, $promotionSlug);
        } catch (\RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        if ($events === []) {
            $this->warn('No Cagematch events were parsed from listing pages.');

            return self::SUCCESS;
        }

        $result = $matcher->match(
            $promotion,
            array_values($events),
            $fromYear,
            $toYear,
            is_string($slug) ? $slug : null,
        );

        if ($result['links'] !== []) {
            $this->newLine();
            $this->info($dryRun ? 'Matches (dry run):' : 'Linked shows:');
            $this->table(
                ['Show', 'Date', 'Cagematch event', 'URL'],
                array_map(
                    fn ($link): array => [
                        $link->show->title,
                        $link->show->date->toDateString(),
                        $link->event->title,
                        $link->url,
                    ],
                    $result['links'],
                ),
            );
        }

        if (! $dryRun) {
            foreach ($result['links'] as $link) {
                $link->show->update(['cagematch_url' => $link->url]);
            }
        }

        foreach ($result['ambiguous'] as $warning) {
            $this->warn($warning);
        }

        if ($result['unmatchedEvents'] !== []) {
            $this->newLine();
            $this->comment('Unmatched Cagematch events in range:');
            foreach ($result['unmatchedEvents'] as $event) {
                $this->line("  {$event->date->toDateString()} — {$event->title} (nr={$event->eventId})");
            }
        }

        if ($result['unmatchedShows']->isNotEmpty()) {
            $this->newLine();
            $this->comment('MEA shows still missing cagematch_url:');
            foreach ($result['unmatchedShows'] as $show) {
                $this->line("  {$show->date->toDateString()} — {$show->title}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Cagematch events parsed', count($events)],
                ['Matched', count($result['links'])],
                ['Ambiguous', count($result['ambiguous'])],
                ['Unmatched Cagematch events', count($result['unmatchedEvents'])],
                ['Unmatched MEA shows', $result['unmatchedShows']->count()],
            ],
        );

        $this->info($dryRun ? 'Dry run complete.' : 'Cagematch linking complete.');

        return self::SUCCESS;
    }

    /**
     * @return array<int, CagematchEvent>
     */
    private function collectEvents(
        CagematchClient $client,
        CagematchListingParser $parser,
        string $promotionSlug,
    ): array {
        $htmlFiles = $this->resolveHtmlFilePaths();

        if ($htmlFiles !== []) {
            return $this->collectEventsFromFiles($parser, $htmlFiles);
        }

        return $this->collectEventsFromHttp($client, $parser, $promotionSlug);
    }

    /**
     * @return list<string>
     */
    private function resolveHtmlFilePaths(): array
    {
        /** @var list<string> $htmlFiles */
        $htmlFiles = array_values(array_filter(
            (array) $this->option('html'),
            fn (mixed $path): bool => is_string($path) && $path !== '',
        ));

        $htmlDir = $this->option('html-dir');

        if (is_string($htmlDir) && $htmlDir !== '') {
            if (! is_dir($htmlDir)) {
                throw new \RuntimeException("HTML directory not found: [{$htmlDir}]");
            }

            $dirFiles = glob(rtrim($htmlDir, '/').'/*.html') ?: [];

            if ($dirFiles === []) {
                throw new \RuntimeException("No .html files found in [{$htmlDir}]");
            }

            sort($dirFiles, SORT_NATURAL);
            $htmlFiles = array_merge($htmlFiles, $dirFiles);
        }

        return $htmlFiles;
    }

    /**
     * @param  list<string>  $htmlFiles
     * @return array<int, CagematchEvent>
     */
    private function collectEventsFromFiles(CagematchListingParser $parser, array $htmlFiles): array
    {
        $events = [];

        foreach ($htmlFiles as $path) {
            if (! is_readable($path)) {
                throw new \RuntimeException("HTML file is not readable: [{$path}]");
            }

            $pageEvents = $parser->parse(file_get_contents($path) ?: '');

            foreach ($pageEvents as $event) {
                $events[$event->eventId] = $event;
            }

            $this->line('  '.basename($path).': '.count($pageEvents).' events');
        }

        return $events;
    }

    /**
     * @return array<int, CagematchEvent>
     */
    private function collectEventsFromHttp(
        CagematchClient $client,
        CagematchListingParser $parser,
        string $promotionSlug,
    ): array {
        $events = [];
        $page = 1;

        while (true) {
            try {
                $html = $client->fetchListingPage($promotionSlug, $page);
            } catch (\RuntimeException $exception) {
                if ($page === 1 && str_contains($exception->getMessage(), 'HTTP 403')) {
                    throw new \RuntimeException(
                        'Cagematch blocked the automated request (HTTP 403). Open the WCW PPV listing in your browser, save each results page as HTML, then re-run with --html=path/to/page.html or --html-dir=path/to/pages/.',
                        previous: $exception,
                    );
                }

                throw $exception;
            }

            $pageEvents = $parser->parse($html);

            if ($pageEvents === []) {
                break;
            }

            foreach ($pageEvents as $event) {
                $events[$event->eventId] = $event;
            }

            $this->line("  Page {$page}: ".count($pageEvents).' events');
            $page++;
        }

        return $events;
    }
}
