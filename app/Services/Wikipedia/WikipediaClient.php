<?php

namespace App\Services\Wikipedia;

use App\Services\MediaWiki\MediaWikiApiClient;

class WikipediaClient extends MediaWikiApiClient
{
    protected function apiEndpoint(): string
    {
        return (string) config('wikipedia.api_endpoint');
    }

    protected function userAgent(): string
    {
        return (string) config('wikipedia.user_agent');
    }

    protected function maxRetries(): int
    {
        return (int) config('wikipedia.max_retries', 3);
    }

    protected function retryBaseDelayMs(): int
    {
        return (int) config('wikipedia.retry_base_delay_ms', 1000);
    }

    protected function maxlag(): int
    {
        return (int) config('wikipedia.maxlag', 5);
    }

    protected function serviceLabel(): string
    {
        return 'Wikipedia';
    }
}
