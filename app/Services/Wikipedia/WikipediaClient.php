<?php

namespace App\Services\Wikipedia;

use App\Data\ResolvedWikipediaPage;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class WikipediaClient
{
    public function fetchWikitext(string $pageTitle): string
    {
        return $this->resolvePage($pageTitle)->wikitext;
    }

    public function resolvePage(string $pageTitle): ResolvedWikipediaPage
    {
        $response = $this->client()->get(config('wikipedia.api_endpoint'), [
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
        $response = $this->client()->get(config('wikipedia.api_endpoint'), [
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

    private function client(): PendingRequest
    {
        return Http::timeout(60)
            ->withHeaders([
                'User-Agent' => config('wikipedia.user_agent'),
            ]);
    }
}
