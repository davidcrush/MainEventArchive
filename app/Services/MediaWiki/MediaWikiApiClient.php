<?php

namespace App\Services\MediaWiki;

use App\Data\ResolvedWikipediaPage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Sleep;
use RuntimeException;

/**
 * Shared client for MediaWiki action APIs (Wikipedia, Fandom, etc.). Subclasses
 * provide the endpoint, User-Agent, and retry policy; this base handles the
 * common request/throttle/backoff loop and page/search resolution.
 */
abstract class MediaWikiApiClient
{
    abstract protected function apiEndpoint(): string;

    abstract protected function userAgent(): string;

    abstract protected function maxRetries(): int;

    abstract protected function retryBaseDelayMs(): int;

    /**
     * MediaWiki maxlag parameter (seconds). Wikimedia-specific; return 0 to omit.
     */
    abstract protected function maxlag(): int;

    /**
     * Human-readable service name used in exception messages.
     */
    protected function serviceLabel(): string
    {
        return 'MediaWiki';
    }

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
            throw new RuntimeException("{$this->serviceLabel()} API request failed for [{$pageTitle}]: ".$response->status());
        }

        $pages = $response->json('query.pages', []);

        if ($pages === []) {
            throw new RuntimeException("{$this->serviceLabel()} page not found: [{$pageTitle}]");
        }

        $page = reset($pages);

        if (isset($page['missing'])) {
            throw new RuntimeException("{$this->serviceLabel()} page not found: [{$pageTitle}]");
        }

        $wikitext = $page['revisions'][0]['slots']['main']['*'] ?? null;

        if (! is_string($wikitext) || $wikitext === '') {
            throw new RuntimeException("{$this->serviceLabel()} page [{$pageTitle}] has no wikitext content.");
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
     * Issue a GET against the MediaWiki API, retrying with backoff when the
     * server reports rate limiting (429) or replication lag (503). Honors the
     * Retry-After header when present so concurrent workers self-throttle.
     *
     * @param  array<string, scalar>  $query
     */
    protected function requestJson(array $query): Response
    {
        $endpoint = $this->apiEndpoint();
        $maxAttempts = max(1, $this->maxRetries());
        $baseDelayMs = max(0, $this->retryBaseDelayMs());
        $maxlag = $this->maxlag();

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

    protected function isThrottled(Response $response): bool
    {
        return in_array($response->status(), [429, 503], true);
    }

    protected function retryDelayMilliseconds(Response $response, int $attempt, int $baseDelayMs): int
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

    protected function client(): PendingRequest
    {
        return Http::timeout(60)
            ->withHeaders([
                'User-Agent' => $this->userAgent(),
            ]);
    }
}
