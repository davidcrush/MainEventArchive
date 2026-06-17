<?php

namespace Tests\Unit;

use App\Services\Fandom\FandomClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class FandomClientTest extends TestCase
{
    public function test_resolve_page_hits_the_fandom_endpoint_without_maxlag(): void
    {
        Http::fake([
            'prowrestling.fandom.com/*' => function ($request) {
                $this->assertStringNotContainsString('maxlag', (string) $request->url());

                return Http::response([
                    'query' => [
                        'pages' => [
                            '42' => [
                                'pageid' => 42,
                                'title' => 'January 1, 1996 Monday Nitro results',
                                'revisions' => [
                                    ['slots' => ['main' => ['*' => '==Results==']]],
                                ],
                            ],
                        ],
                    ],
                ]);
            },
        ]);

        $resolved = app(FandomClient::class)->resolvePage('January 1, 1996 Monday Nitro results');

        $this->assertSame('January 1, 1996 Monday Nitro results', $resolved->canonicalTitle);
    }

    public function test_resolve_page_retries_after_rate_limit_and_honors_retry_after(): void
    {
        Sleep::fake();

        Http::fakeSequence('prowrestling.fandom.com/*')
            ->push('Slow down', 429, ['Retry-After' => '3'])
            ->push([
                'query' => [
                    'pages' => [
                        '42' => [
                            'pageid' => 42,
                            'title' => 'January 1, 1996 Monday Nitro results',
                            'revisions' => [
                                ['slots' => ['main' => ['*' => '==Results==']]],
                            ],
                        ],
                    ],
                ],
            ], 200);

        $resolved = app(FandomClient::class)->resolvePage('January 1, 1996 Monday Nitro results');

        $this->assertSame('January 1, 1996 Monday Nitro results', $resolved->canonicalTitle);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds === 3000.0, 1);
    }

    public function test_resolve_page_gives_up_after_max_retries(): void
    {
        config(['fandom.max_retries' => 2]);
        Sleep::fake();

        Http::fake([
            'prowrestling.fandom.com/*' => Http::response('Slow down', 429),
        ]);

        $this->expectExceptionMessageMatches('/Fandom API request failed.*429/');

        app(FandomClient::class)->resolvePage('January 1, 1996 Monday Nitro results');
    }
}
