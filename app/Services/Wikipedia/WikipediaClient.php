<?php

namespace App\Services\Wikipedia;

use App\Data\ResolvedWikipediaPage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

class WikipediaClient
{
    public function fetchWikitext(string $pageTitle): string
    {
        return $this->resolvePage($pageTitle)->wikitext;
    }

    public function resolvePage(string $pageTitle): ResolvedWikipediaPage
    {
        $response = $this->requestJson([
            'action' => 'query',
            'format' => 'json',
            'titles' => $pageTitle,
            'prop' => 'revisions',
            'rvslots' => 'main',
            'rvprop' => 'content',
            'redirects' => 1,
        ]);

        if (! $response->successful()) {
            throw new RuntimeException("Wikipedia API request failed for [{$pageTitle}]: ".$response->status());
        }

        $pages = $response->json('query.pages', []);

        if ($pages === []) {
            throw new RuntimeException("Wikipedia page not found: [{$pageTitle}]");
        }

        $page = reset($pages);

        if (isset($page['missing'])) {
            throw new RuntimeException("Wikipedia page not found: [{$pageTitle}]");
        }

        $wikitext = $page['revisions'][0]['slots']['main']['*'] ?? null;

        if (! is_string($wikitext) || $wikitext === '') {
            throw new RuntimeException("Wikipedia page [{$pageTitle}] has no wikitext content.");
        }

        $redirectFrom = $response->json('query.redirects.0.from');

        return new ResolvedWikipediaPage(
            canonicalTitle: $page['title'] ?? $pageTitle,
            redirectFrom: is_string($redirectFrom) ? $redirectFrom : null,
            wikitext: $wikitext,
        );
    }

    public function searchPageTitle(string $query): ?string
    {
        return $this->searchPageTitles($query)[0] ?? null;
    }

    /**
     * @return list<string>
     */
    public function searchPageTitles(string $query): array
    {
        $response = $this->requestJson([
            'action' => 'query',
            'format' => 'json',
            'list' => 'search',
            'srsearch' => $query,
            'srlimit' => 5,
        ]);

        if (! $response->successful()) {
            return [];
        }

        $results = $response->json('query.search', []);

        if ($results === []) {
            return [];
        }

        return array_values(array_filter(array_map(
            static fn (array $result): ?string => $result['title'] ?? null,
            $results,
        )));
    }

    /**
     * Issue a GET against the Wikipedia API, retrying with backoff when the
     * server reports rate limiting (429) or replication lag (503). Honors the
     * Retry-After header when present so concurrent workers self-throttle.
     *
     * @param  array<string, scalar>  $query
     */
    private function requestJson(array $query): Response
    {
        $endpoint = (string) config('wikipedia.api_endpoint');
        $maxAttempts = max(1, (int) config('wikipedia.max_retries', 3));
        $baseDelayMs = max(0, (int) config('wikipedia.retry_base_delay_ms', 1000));
        $maxlag = (int) config('wikipedia.maxlag', 5);

        if ($maxlag > 0) {
            $query['maxlag'] = $maxlag;
        }

        $attempt = 0;

        while (true) {
            $attempt++;
            $response = $this->client()->get($endpoint, $query);

            if (! $this->isThrottled($response) || $attempt >= $maxAttempts) {
                return $response;
            }

            Sleep::for($this->retryDelayMilliseconds($response, $attempt, $baseDelayMs))->milliseconds();
        }
    }

    private function isThrottled(Response $response): bool
    {
        return in_array($response->status(), [429, 503], true);
    }

    private function retryDelayMilliseconds(Response $response, int $attempt, int $baseDelayMs): int
    {
        $retryAfter = trim((string) $response->header('Retry-After'));

        if ($retryAfter !== '' && is_numeric($retryAfter)) {
            return (int) round((float) $retryAfter * 1000);
        }

        if ($retryAfter !== '' && ($timestamp = strtotime($retryAfter)) !== false) {
            return max(0, ($timestamp - time()) * 1000);
        }

        return $baseDelayMs * (2 ** ($attempt - 1));
    }

    private function client(): PendingRequest
    {
        return Http::timeout(60)
            ->withHeaders([
                'User-Agent' => config('wikipedia.user_agent'),
            ]);
    }
}
