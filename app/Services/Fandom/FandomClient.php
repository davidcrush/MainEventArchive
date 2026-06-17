<?php

namespace App\Services\Fandom;

use App\Services\MediaWiki\MediaWikiApiClient;

class FandomClient extends MediaWikiApiClient
{
    protected function apiEndpoint(): string
    {
        return (string) config('fandom.api_endpoint');
    }

    protected function userAgent(): string
    {
        return (string) config('fandom.user_agent');
    }

    protected function maxRetries(): int
    {
        return (int) config('fandom.max_retries', 4);
    }

    protected function retryBaseDelayMs(): int
    {
        return (int) config('fandom.retry_base_delay_ms', 1000);
    }

    protected function maxlag(): int
    {
        return (int) config('fandom.maxlag', 0);
    }

    protected function serviceLabel(): string
    {
        return 'Fandom';
    }
}
