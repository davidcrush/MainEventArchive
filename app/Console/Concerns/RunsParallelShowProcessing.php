<?php

namespace App\Console\Concerns;

use Illuminate\Contracts\Process\ProcessResult;
use Illuminate\Process\Pool;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Process;

/**
 * Shared orchestration for splitting a date-ordered show list across concurrent
 * child artisan workers. The command using this trait keeps its per-show logic
 * untouched; this trait only handles worker dispatch, slicing, and aggregation.
 */
trait RunsParallelShowProcessing
{
    /**
     * Clamp a requested worker count to a sane range bounded by configuration.
     */
    protected function resolveWorkerCount(int $requested): int
    {
        $max = max(1, (int) config('wikipedia.max_workers', 4));

        return max(1, min($requested, $max));
    }

    /**
     * Determine whether the current invocation is a child worker process.
     */
    protected function isParallelWorker(): bool
    {
        return $this->option('of') !== null && (int) $this->option('of') > 1;
    }

    /**
     * Reduce a deterministic, ordered collection to this worker's round-robin slice.
     *
     * @template TValue
     *
     * @param  Collection<int, TValue>  $items
     * @return Collection<int, TValue>
     */
    protected function sliceForWorker(Collection $items): Collection
    {
        $total = (int) $this->option('of');
        $index = (int) $this->option('chunk');

        if ($total <= 1) {
            return $items->values();
        }

        return $items
            ->values()
            ->filter(static fn ($item, int $position): bool => $position % $total === $index)
            ->values();
    }

    /**
     * Spawn the requested number of child workers and wait for them to finish.
     *
     * @param  list<string>  $sharedArguments  CLI tokens passed to every worker.
     * @return Collection<int, ProcessResult>
     */
    protected function runWorkerPool(string $commandName, array $sharedArguments, int $workers): Collection
    {
        $commands = [];

        for ($chunk = 0; $chunk < $workers; $chunk++) {
            $commands[] = array_merge(
                [PHP_BINARY, base_path('artisan'), $commandName],
                $sharedArguments,
                ["--chunk={$chunk}", "--of={$workers}", '--json'],
            );
        }

        $results = Process::pool(function (Pool $pool) use ($commands): void {
            foreach ($commands as $command) {
                $pool->path(base_path())->timeout(0)->command($command);
            }
        })->wait();

        return Collection::range(0, $workers - 1)->map(
            static fn (int $key): ProcessResult => $results[$key],
        );
    }

    /**
     * Decode newline-delimited JSON objects emitted by a worker process.
     *
     * @return list<array<string, mixed>>
     */
    protected function decodeWorkerJsonLines(string $output): array
    {
        $records = [];

        foreach (preg_split('/\r?\n/', trim($output)) ?: [] as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            $decoded = json_decode($line, true);

            if (is_array($decoded)) {
                $records[] = $decoded;
            }
        }

        return $records;
    }

    /**
     * Emit a single machine-readable JSON line on stdout.
     *
     * @param  array<string, mixed>  $payload
     */
    protected function emitJsonLine(array $payload): void
    {
        $this->line((string) json_encode($payload, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
    }
}
