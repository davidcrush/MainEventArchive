<?php

namespace App\Importers;

use App\Enums\ShowType;
use App\Exceptions\WikipediaMatchCountMismatchException;
use App\Importers\Concerns\PersistsParsedMatches;
use App\Models\Promotion;
use App\Models\Show;
use App\Services\Fandom\FandomNitroPageResolver;
use App\Services\Fandom\FandomNitroResultsParser;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use RuntimeException;

/**
 * Enriches WCW Monday Nitro catalog shows with per-episode match cards sourced
 * from prowrestling.fandom.com (CC BY-SA 3.0). Cards are persisted with
 * spoiler-safe ordering; shows whose parsed match count disagrees with the
 * page's declared bullet count are skipped (left PendingReview) so we never
 * store partial/leaky cards.
 */
class FandomNitroImporter
{
    use PersistsParsedMatches;

    private const NITRO_TITLE_PREFIX = 'WCW Monday Nitro';

    public function __construct(
        private readonly FandomNitroPageResolver $pageResolver,
        private readonly FandomNitroResultsParser $resultsParser,
    ) {}

    /**
     * @return array{imported: int, skipped: int, matches: int, shows: list<array{show: Show, status: string, matchCount: int, message: ?string}>}
     */
    public function import(
        Promotion $promotion,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?string $identifier = null,
        bool $dryRun = false,
    ): array {
        $shows = $this->resolveShows($promotion, $fromDate, $toDate, $identifier);

        $imported = 0;
        $skipped = 0;
        $matchTotal = 0;
        $rows = [];

        foreach ($shows as $show) {
            try {
                $page = $this->pageResolver->resolve($show);
                $parsedMatches = $this->resultsParser->parse($page->wikitext);
                $matchCount = count($parsedMatches);

                if (! $dryRun) {
                    $this->persistMatches($show, $parsedMatches);

                    $show->update([
                        'source' => 'fandom',
                        'source_url' => 'https://prowrestling.fandom.com/wiki/'.str_replace(' ', '_', $page->canonicalTitle),
                        'imported_at' => now(),
                    ]);
                }

                $imported++;
                $matchTotal += $matchCount;
                $rows[] = [
                    'show' => $show,
                    'status' => $dryRun ? 'DRY-RUN' : 'OK',
                    'matchCount' => $matchCount,
                    'message' => null,
                ];
            } catch (WikipediaMatchCountMismatchException $exception) {
                $skipped++;
                $rows[] = [
                    'show' => $show,
                    'status' => 'SKIP',
                    'matchCount' => $exception->parsedCount,
                    'message' => $exception->getMessage(),
                ];
            } catch (RuntimeException $exception) {
                $skipped++;
                $rows[] = [
                    'show' => $show,
                    'status' => 'SKIP',
                    'matchCount' => 0,
                    'message' => $exception->getMessage(),
                ];
            }
        }

        return [
            'imported' => $imported,
            'skipped' => $skipped,
            'matches' => $matchTotal,
            'shows' => $rows,
        ];
    }

    /**
     * @return Collection<int, Show>
     */
    private function resolveShows(Promotion $promotion, ?string $fromDate, ?string $toDate, ?string $identifier): Collection
    {
        return Show::query()
            ->where('promotion_id', $promotion->id)
            ->where('show_type', ShowType::Tv->value)
            ->where('title', 'like', self::NITRO_TITLE_PREFIX.'%')
            ->when($identifier !== null && $identifier !== '', fn (Builder $query) => $query->where(
                fn (Builder $inner) => $inner->where('slug', $identifier)->orWhere('title', $identifier),
            ))
            ->when($fromDate !== null, fn (Builder $query) => $query->whereDate('date', '>=', $fromDate))
            ->when($toDate !== null, fn (Builder $query) => $query->whereDate('date', '<=', $toDate))
            ->orderBy('date')
            ->get();
    }
}
