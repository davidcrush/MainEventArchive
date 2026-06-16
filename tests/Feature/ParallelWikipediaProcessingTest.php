<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Process\PendingProcess;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Process;
use Tests\TestCase;

class ParallelWikipediaProcessingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::preventStrayRequests();
    }

    public function test_verify_worker_processes_only_its_round_robin_slice(): void
    {
        $this->seedWweShows();
        $this->fakeResolvableWikipedia();

        $this->artisan('shows:verify-wikipedia', [
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--chunk' => 0,
            '--of' => 2,
            '--json' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('"slug":"backlash-1999"')
            ->expectsOutputToContain('"slug":"armageddon-1999"')
            ->doesntExpectOutputToContain('"slug":"no-mercy-1999"');
    }

    public function test_verify_second_worker_processes_complementary_slice(): void
    {
        $this->seedWweShows();
        $this->fakeResolvableWikipedia();

        $this->artisan('shows:verify-wikipedia', [
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--chunk' => 1,
            '--of' => 2,
            '--json' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('"slug":"no-mercy-1999"')
            ->doesntExpectOutputToContain('"slug":"backlash-1999"')
            ->doesntExpectOutputToContain('"slug":"armageddon-1999"');
    }

    public function test_verify_orchestrator_aggregates_worker_results(): void
    {
        $this->seedWweShows();

        Process::fake([
            '*shows:verify-wikipedia*' => Process::sequence()
                ->push(Process::result(output: implode("\n", [
                    json_encode(['slug' => 'backlash-1999', 'status' => 'ok', 'title' => 'Backlash (1999)']),
                    json_encode(['slug' => 'armageddon-1999', 'status' => 'ok', 'title' => 'Armageddon (1999)']),
                ])))
                ->push(Process::result(output: json_encode([
                    'slug' => 'no-mercy-1999',
                    'status' => 'fail',
                    'title' => 'No Mercy 1999',
                    'message' => 'Could not import Wikipedia card for [No Mercy 1999].',
                ]))),
        ]);

        $this->artisan('shows:verify-wikipedia', [
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--workers' => 2,
        ])
            ->assertFailed()
            ->expectsOutputToContain('OK  backlash-1999')
            ->expectsOutputToContain('FAIL no-mercy-1999')
            ->expectsOutputToContain('Could not import Wikipedia card for [No Mercy 1999].');
    }

    public function test_verify_orchestrator_clamps_workers_to_configured_maximum(): void
    {
        config(['wikipedia.max_workers' => 2]);
        $this->seedWweShows();

        Process::fake([
            '*shows:verify-wikipedia*' => Process::result(output: json_encode([
                'slug' => 'backlash-1999',
                'status' => 'ok',
                'title' => 'Backlash (1999)',
            ])),
        ]);

        $this->artisan('shows:verify-wikipedia', [
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--workers' => 9,
        ]);

        Process::assertRanTimes(
            fn (PendingProcess $process): bool => str_contains($this->commandString($process), 'shows:verify-wikipedia')
                && str_contains($this->commandString($process), '--of=2'),
            2,
        );
    }

    public function test_import_worker_persists_only_its_slice(): void
    {
        $this->seedWweShows();
        $this->fakeResolvableWikipedia();

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--chunk' => 0,
            '--of' => 2,
            '--json' => true,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('"updated":2');

        $this->assertNotNull(Show::query()->where('slug', 'backlash-1999')->value('source_url'));
        $this->assertNotNull(Show::query()->where('slug', 'armageddon-1999')->value('source_url'));
        $this->assertNull(Show::query()->where('slug', 'no-mercy-1999')->value('source_url'));
    }

    public function test_import_orchestrator_sums_worker_summaries(): void
    {
        $this->seedWweShows();

        Process::fake([
            '*shows:import*' => Process::sequence()
                ->push(Process::result(output: json_encode([
                    'created' => 5,
                    'updated' => 2,
                    'skipped' => 0,
                    'warnings' => [],
                ])))
                ->push(Process::result(output: json_encode([
                    'created' => 3,
                    'updated' => 1,
                    'skipped' => 0,
                    'warnings' => [],
                ]))),
        ]);

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--workers' => 2,
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('Import complete.');

        Process::assertRanTimes(
            fn (PendingProcess $process): bool => str_contains($this->commandString($process), 'shows:import'),
            2,
        );
    }

    public function test_import_orchestrator_fails_strict_when_worker_skips_shows(): void
    {
        $this->seedWweShows();

        Process::fake([
            '*shows:import*' => Process::sequence()
                ->push(Process::result(output: json_encode([
                    'created' => 2,
                    'updated' => 1,
                    'skipped' => 0,
                    'warnings' => [],
                ]), exitCode: 0))
                ->push(Process::result(output: json_encode([
                    'created' => 0,
                    'updated' => 0,
                    'skipped' => 1,
                    'warnings' => ['Could not import Wikipedia card for [No Mercy 1999].'],
                ]), exitCode: 1)),
        ]);

        $this->artisan('shows:import', [
            'source' => 'wikipedia',
            '--promotion' => 'wwe',
            '--from' => 1999,
            '--to' => 1999,
            '--workers' => 2,
            '--strict' => true,
        ])
            ->assertFailed()
            ->expectsOutputToContain('Could not import Wikipedia card for [No Mercy 1999].');
    }

    private function commandString(PendingProcess $process): string
    {
        return is_array($process->command)
            ? implode(' ', $process->command)
            : (string) $process->command;
    }

    private function seedWweShows(): void
    {
        $promotion = Promotion::factory()->wwe()->create();

        $shows = [
            ['title' => 'Backlash 1999', 'slug' => 'backlash-1999', 'date' => '1999-01-01'],
            ['title' => 'No Mercy 1999', 'slug' => 'no-mercy-1999', 'date' => '1999-02-01'],
            ['title' => 'Armageddon 1999', 'slug' => 'armageddon-1999', 'date' => '1999-03-01'],
        ];

        foreach ($shows as $show) {
            Show::factory()->create([
                'promotion_id' => $promotion->id,
                'title' => $show['title'],
                'slug' => $show['slug'],
                'date' => $show['date'],
                'source' => 'manual',
                'source_url' => null,
            ]);
        }
    }

    private function fakeResolvableWikipedia(): void
    {
        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response(['query' => ['search' => []]]);
            }

            $title = urldecode($query['titles'] ?? '');

            return Http::response([
                'query' => [
                    'pages' => [
                        '1' => [
                            'pageid' => 1,
                            'title' => $title,
                            'revisions' => [
                                ['slots' => ['main' => ['*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Wrestler A]] defeated [[Wrestler B]]
| stip1 = Singles match
| time1 = 10:00
}}
WIKI]]],
                            ],
                        ],
                    ],
                ],
            ]);
        });
    }
}
