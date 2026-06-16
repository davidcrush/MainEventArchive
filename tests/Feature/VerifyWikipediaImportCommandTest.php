<?php

namespace Tests\Feature;

use App\Models\Promotion;
use App\Models\Show;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class VerifyWikipediaImportCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_verify_command_reports_success_for_resolvable_show(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'King Of The Ring 2000',
            'slug' => 'king-of-the-ring-2000',
            'date' => '2000-06-25',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response(['query' => ['search' => []]]);
            }

            return Http::response([
                'query' => [
                    'pages' => [
                        '2000' => [
                            'pageid' => 2000,
                            'title' => 'King of the Ring (2000)',
                            'revisions' => [
                                ['slots' => ['main' => ['*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
| match1 = [[Kurt Angle]] defeated [[Chris Benoit]]
| stip1 = Singles match
| time1 = 12:00
}}
WIKI]]],
                            ],
                        ],
                    ],
                ],
            ]);
        });

        $this->artisan('shows:verify-wikipedia', [
            '--slug' => 'king-of-the-ring-2000',
        ])
            ->assertSuccessful()
            ->expectsOutputToContain('OK  king-of-the-ring-2000');
    }

    public function test_verify_command_fails_with_detailed_attempt_log(): void
    {
        $promotion = Promotion::factory()->wwe()->create();
        Show::factory()->create([
            'promotion_id' => $promotion->id,
            'title' => 'King Of The Ring 2000',
            'slug' => 'king-of-the-ring-2000',
            'date' => '2000-06-25',
        ]);

        Http::fake(function ($request) {
            parse_str((string) parse_url((string) $request->url(), PHP_URL_QUERY), $query);

            if (($query['list'] ?? null) === 'search') {
                return Http::response(['query' => ['search' => []]]);
            }

            return Http::response([
                'query' => [
                    'pages' => [
                        '404' => [
                            'title' => urldecode($query['titles'] ?? ''),
                            'missing' => true,
                        ],
                    ],
                ],
            ]);
        });

        $this->artisan('shows:verify-wikipedia', [
            '--slug' => 'king-of-the-ring-2000',
        ])
            ->assertFailed()
            ->expectsOutputToContain('Shows that need attention:')
            ->expectsOutputToContain('Attempts:');
    }
}
