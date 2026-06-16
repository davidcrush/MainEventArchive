<?php

namespace App\Console\Commands;

use App\Console\Concerns\RunsParallelShowProcessing;
use App\Exceptions\WikipediaImportResolutionException;
use App\Importers\WikipediaShowImporter;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RuntimeException;

class VerifyWikipediaImportCommand extends Command
{
    use RunsParallelShowProcessing;

    protected $signature = 'shows:verify-wikipedia
                            {--promotion= : Promotion slug filter (required unless --slug is set)}
                            {--from=1993 : Start year (inclusive)}
                            {--to=2001 : End year (inclusive)}
                            {--slug= : Verify a single show slug}
                            {--workers=1 : Number of concurrent worker processes}
                            {--chunk= : Internal: worker slice index}
                            {--of= : Internal: total worker count}
                            {--json : Internal: emit machine-readable results}';

    protected $description = 'Dry-run Wikipedia page resolution for catalog shows and report failures';

    public function handle(WikipediaShowImporter $importer): int
    {
        $slug = $this->option('slug');
        $promotionSlug = $this->option('promotion');
        $fromYear = (int) $this->option('from');
        $toYear = (int) $this->option('to');

        if ($fromYear > $toYear) {
            $this->error('--from year must be less than or equal to --to year.');

            return self::FAILURE;
        }

        if (blank($slug) && blank($promotionSlug)) {
            $this->error('Provide --promotion for bulk verification or --slug for a single show.');

            return self::FAILURE;
        }

        $shows = $this->resolveShows(
            is_string($slug) && $slug !== '' ? $slug : null,
            is_string($promotionSlug) && $promotionSlug !== '' ? $promotionSlug : null,
            $fromYear,
            $toYear,
        );

        if ($shows->isEmpty()) {
            $this->warn('No shows matched the verification filters.');

            return self::FAILURE;
        }

        $emitJson = (bool) $this->option('json');

        if ($this->isParallelWorker()) {
            return $this->runVerification($importer, $this->sliceForWorker($shows), $emitJson);
        }

        $workers = $this->resolveWorkerCount(min((int) $this->option('workers'), $shows->count()));

        if ($workers > 1) {
            return $this->runParallelVerification(
                $shows->count(),
                is_string($promotionSlug) ? $promotionSlug : null,
                $fromYear,
                $toYear,
                $workers,
            );
        }

        return $this->runVerification($importer, $shows, $emitJson);
    }

    /**
     * @param  Collection<int, Show>  $shows
     */
    private function runVerification(WikipediaShowImporter $importer, Collection $shows, bool $emitJson): int
    {
        if (! $emitJson) {
            $this->info(sprintf('Verifying Wikipedia import readiness for %d show(s)...', $shows->count()));
        }

        $failures = [];
        $successes = 0;

        foreach ($shows as $show) {
            try {
                [$page] = $importer->resolvePageForShow($show);
                $successes++;

                if ($emitJson) {
                    $this->emitJsonLine([
                        'slug' => $show->slug,
                        'status' => 'ok',
                        'title' => $page->canonicalTitle,
                    ]);
                } else {
                    $this->line("  OK  {$show->slug} → {$page->canonicalTitle}");
                }
            } catch (WikipediaImportResolutionException $exception) {
                $failures[] = $this->recordFailure($show, $exception->getMessage(), $emitJson);
            } catch (RuntimeException $exception) {
                $failures[] = $this->recordFailure(
                    $show,
                    "{$show->title} ({$show->slug}): {$exception->getMessage()}",
                    $emitJson,
                );
            }
        }

        if ($emitJson) {
            return $failures === [] ? self::SUCCESS : self::FAILURE;
        }

        return $this->renderVerificationSummary($shows->count(), $successes, $failures);
    }

    /**
     * @return array{slug: string, title: string, message: string}
     */
    private function recordFailure(Show $show, string $message, bool $emitJson): array
    {
        $failure = [
            'slug' => $show->slug,
            'title' => $show->title,
            'message' => $message,
        ];

        if ($emitJson) {
            $this->emitJsonLine([
                'slug' => $show->slug,
                'status' => 'fail',
                'title' => $show->title,
                'message' => $message,
            ]);
        } else {
            $this->warn("  FAIL {$show->slug}");
        }

        return $failure;
    }

    private function runParallelVerification(int $total, ?string $promotionSlug, int $fromYear, int $toYear, int $workers): int
    {
        $this->info(sprintf('Verifying %d show(s) across %d parallel worker(s)...', $total, $workers));

        $arguments = array_values(array_filter([
            $promotionSlug !== null ? "--promotion={$promotionSlug}" : null,
            "--from={$fromYear}",
            "--to={$toYear}",
        ]));

        $results = $this->runWorkerPool('shows:verify-wikipedia', $arguments, $workers);

        $failures = [];
        $successes = 0;

        foreach ($results as $result) {
            foreach ($this->decodeWorkerJsonLines($result->output()) as $record) {
                $status = $record['status'] ?? null;

                if ($status === 'ok') {
                    $successes++;
                    $this->line("  OK  {$record['slug']} → {$record['title']}");
                } elseif ($status === 'fail') {
                    $failures[] = [
                        'slug' => (string) ($record['slug'] ?? ''),
                        'title' => (string) ($record['title'] ?? ''),
                        'message' => (string) ($record['message'] ?? ''),
                    ];
                    $this->warn("  FAIL {$record['slug']}");
                }
            }

            if (! $result->successful() && trim($result->errorOutput()) !== '') {
                $this->warn(trim($result->errorOutput()));
            }
        }

        $processed = $successes + count($failures);

        if ($processed < $total) {
            $failures[] = [
                'slug' => '',
                'title' => '',
                'message' => sprintf(
                    'A worker process returned results for only %d of %d show(s). Inspect worker error output above.',
                    $processed,
                    $total,
                ),
            ];
        }

        return $this->renderVerificationSummary($total, $successes, $failures);
    }

    /**
     * @param  list<array{slug: string, title: string, message: string}>  $failures
     */
    private function renderVerificationSummary(int $total, int $successes, array $failures): int
    {
        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Verified', $total],
                ['Ready', $successes],
                ['Failed', count($failures)],
            ],
        );

        if ($failures !== []) {
            $this->newLine();
            $this->error('Shows that need attention:');

            foreach ($failures as $failure) {
                $this->newLine();
                $this->line($failure['message']);
            }

            return self::FAILURE;
        }

        $this->info('All shows resolved to parseable Wikipedia pages.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Show>
     */
    private function resolveShows(?string $slug, ?string $promotionSlug, int $fromYear, int $toYear)
    {
        if ($slug !== null) {
            $show = Show::query()->where('slug', $slug)->first();

            return $show === null ? collect() : collect([$show]);
        }

        if ($promotionSlug !== null && Promotion::query()->where('slug', $promotionSlug)->doesntExist()) {
            $this->error("Promotion [{$promotionSlug}] was not found.");

            return collect();
        }

        return Show::query()
            ->when($promotionSlug !== null, fn ($query) => $query->whereHas(
                'promotion',
                fn ($promotionQuery) => $promotionQuery->where('slug', $promotionSlug),
            ))
            ->whereBetween('date', [
                "{$fromYear}-01-01",
                "{$toYear}-12-31",
            ])
            ->orderBy('date')
            ->get();
    }
}
