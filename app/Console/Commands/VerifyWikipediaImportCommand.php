<?php

namespace App\Console\Commands;

use App\Exceptions\WikipediaImportResolutionException;
use App\Importers\WikipediaShowImporter;
use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use RuntimeException;

class VerifyWikipediaImportCommand extends Command
{
    protected $signature = 'shows:verify-wikipedia
                            {--promotion= : Promotion slug filter (required unless --slug is set)}
                            {--from=1993 : Start year (inclusive)}
                            {--to=2001 : End year (inclusive)}
                            {--slug= : Verify a single show slug}';

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

        $this->info(sprintf('Verifying Wikipedia import readiness for %d show(s)...', $shows->count()));

        $failures = [];
        $successes = 0;

        foreach ($shows as $show) {
            try {
                [$page] = $importer->resolvePageForShow($show);
                $successes++;
                $this->line("  OK  {$show->slug} → {$page->canonicalTitle}");
            } catch (WikipediaImportResolutionException $exception) {
                $failures[] = [
                    'slug' => $show->slug,
                    'title' => $show->title,
                    'message' => $exception->getMessage(),
                ];
                $this->warn("  FAIL {$show->slug}");
            } catch (RuntimeException $exception) {
                $failures[] = [
                    'slug' => $show->slug,
                    'title' => $show->title,
                    'message' => "{$show->title} ({$show->slug}): {$exception->getMessage()}",
                ];
                $this->warn("  FAIL {$show->slug}");
            }
        }

        $this->newLine();
        $this->table(
            ['Metric', 'Count'],
            [
                ['Verified', $shows->count()],
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
