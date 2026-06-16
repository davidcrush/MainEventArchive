<?php

namespace Tests\Unit;

use App\Services\Wikipedia\WikipediaClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use Tests\TestCase;

class WikipediaClientTest extends TestCase
{
    public function test_resolve_page_retries_after_rate_limit_and_honors_retry_after(): void
    {
        Sleep::fake();

        Http::fakeSequence('en.wikipedia.org/*')
            ->push('Slow down', 429, ['Retry-After' => '2'])
            ->push([
                'query' => [
                    'pages' => [
                        '12345' => [
                            'pageid' => 12345,
                            'title' => 'Starrcade (1996)',
                            'revisions' => [
                                ['slots' => ['main' => ['*' => '==Results==']]],
                            ],
                        ],
                    ],
                ],
            ], 200);

        $resolved = app(WikipediaClient::class)->resolvePage('Starrcade (1996)');

        $this->assertSame('Starrcade (1996)', $resolved->canonicalTitle);
        Sleep::assertSlept(fn ($duration) => $duration->totalMilliseconds === 2000.0, 1);
    }

    public function test_resolve_page_gives_up_after_max_retries(): void
    {
        config(['wikipedia.max_retries' => 2]);
        Sleep::fake();

        Http::fake([
            'en.wikipedia.org/*' => Http::response('Slow down', 429),
        ]);

        $this->expectExceptionMessageMatches('/Wikipedia API request failed.*429/');

        app(WikipediaClient::class)->resolvePage('Starrcade (1996)');
    }

    public function test_search_returns_empty_when_rate_limit_persists(): void
    {
        config(['wikipedia.max_retries' => 2]);
        Sleep::fake();

        Http::fake([
            'en.wikipedia.org/*' => Http::response('Slow down', 429),
        ]);

        $this->assertSame([], app(WikipediaClient::class)->searchPageTitles('Starrcade 1996'));

        Sleep::assertSlept(fn () => true, 1);
    }

    public function test_fetch_wikitext_requests_redirect_resolution(): void
    {
        Http::fake([
            'en.wikipedia.org/*' => function ($request) {
                $this->assertStringContainsString('redirects=1', (string) $request->url());

                return Http::response([
                    'query' => [
                        'pages' => [
                            '52804542' => [
                                'pageid' => 52804542,
                                'title' => 'The Great American Bash (1995)',
                                'revisions' => [
                                    [
                                        'slots' => [
                                            'main' => [
                                                '*' => <<<'WIKI'
==Results==
{{Pro Wrestling results table
|match1=[[Ric Flair]] defeated [[Randy Savage]]
|stip1=Singles match
|time1=14:42
}}
WIKI,
                                            ],
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ]);
            },
        ]);

        $wikitext = app(WikipediaClient::class)->fetchWikitext('Great American Bash (1995)');

        $this->assertStringContainsString('==Results==', $wikitext);
        $this->assertStringContainsString('Ric Flair', $wikitext);
    }

    public function test_resolve_page_returns_canonical_title_and_redirect_source(): void
    {
        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'redirects' => [
                        ['from' => 'Rosemont Horizon', 'to' => 'Allstate Arena'],
                    ],
                    'pages' => [
                        '12345' => [
                            'pageid' => 12345,
                            'title' => 'Allstate Arena',
                            'revisions' => [
                                [
                                    'slots' => [
                                        'main' => [
                                            '*' => '==Lead==',
                                        ],
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $resolved = app(WikipediaClient::class)->resolvePage('Rosemont Horizon');

        $this->assertSame('Allstate Arena', $resolved->canonicalTitle);
        $this->assertSame('Rosemont Horizon', $resolved->redirectFrom);
        $this->assertSame('==Lead==', $resolved->wikitext);
    }

    public function test_search_page_titles_returns_multiple_results(): void
    {
        Http::fake([
            'en.wikipedia.org/*' => Http::response([
                'query' => [
                    'search' => [
                        ['title' => 'WCW Bash at the Beach'],
                        ['title' => 'The Great American Bash (1995)'],
                    ],
                ],
            ]),
        ]);

        $titles = app(WikipediaClient::class)->searchPageTitles('Great American Bash 1995 WCW');

        $this->assertSame([
            'WCW Bash at the Beach',
            'The Great American Bash (1995)',
        ], $titles);
    }
}
