<?php

namespace App\Services\Cagematch;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class CagematchClient
{
    public function fetchListingPage(string $promotionSlug, int $page): string
    {
        $promotionConfig = $this->promotionConfig($promotionSlug);

        $response = $this->client()->get($this->listingUrl($promotionConfig, $page));

        $this->sleepBetweenRequests();

        if (! $response->successful()) {
            throw new RuntimeException(
                "Cagematch listing request failed for [{$promotionSlug}] page {$page}: HTTP {$response->status()}",
            );
        }

        return $response->body();
    }

    /**
     * @return array{listing_promotion_nr: int, listing_showtype: string}
     */
    public function promotionConfig(string $promotionSlug): array
    {
        $config = config("cagematch.promotions.{$promotionSlug}");

        if (! is_array($config)) {
            throw new RuntimeException("No Cagematch config found for promotion [{$promotionSlug}].");
        }

        return $config;
    }

    /**
     * @param  array{listing_promotion_nr: int, listing_showtype: string}  $promotionConfig
     */
    public function listingUrl(array $promotionConfig, int $page): string
    {
        $query = http_build_query([
            'id' => config('cagematch.listing_view_id'),
            'nr' => $promotionConfig['listing_promotion_nr'],
            'page' => $page,
            'showtype' => $promotionConfig['listing_showtype'],
        ]);

        return rtrim(config('cagematch.base_url'), '/').'/?'.$query;
    }

    public function eventUrl(int $eventId): string
    {
        return sprintf(config('cagematch.event_url_template'), $eventId);
    }

    private function client(): PendingRequest
    {
        return Http::timeout(30)
            ->withHeaders([
                'User-Agent' => config('cagematch.user_agent'),
                'Accept' => 'text/html,application/xhtml+xml',
            ]);
    }

    private function sleepBetweenRequests(): void
    {
        $delayMs = (int) config('cagematch.request_delay_ms', 0);

        if ($delayMs > 0) {
            usleep($delayMs * 1000);
        }
    }
}
