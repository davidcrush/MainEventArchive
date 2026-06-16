<?php

return [

    'api_endpoint' => 'https://en.wikipedia.org/w/api.php',

    'user_agent' => 'MainEventArchive/1.0 (https://github.com/main-event-archive; contact@davidcrush.com)',

    /*
     * Upper bound on concurrent child workers for shows:verify-wikipedia and
     * shows:import. Keep this modest; the Wikipedia API tolerates a few
     * concurrent requests given our descriptive User-Agent, but high values
     * trigger HTTP 429 rate limiting and offer diminishing returns.
     */
    'max_workers' => (int) env('WIKIPEDIA_MAX_WORKERS', 3),

    /*
     * Per-request retry policy. When the API returns 429 (rate limited) or 503
     * (replication lag), the request is retried up to max_retries times. The
     * Retry-After header is honored when present; otherwise the delay grows
     * exponentially from retry_base_delay_ms.
     */
    'max_retries' => (int) env('WIKIPEDIA_MAX_RETRIES', 4),

    'retry_base_delay_ms' => (int) env('WIKIPEDIA_RETRY_BASE_DELAY_MS', 1000),

    /*
     * MediaWiki maxlag parameter (seconds). Asks Wikimedia to reject requests
     * with 503 when database replicas are lagging, which we then retry. This is
     * the recommended polite-citizen setting for bulk API usage. Set to 0 to
     * disable.
     */
    'maxlag' => (int) env('WIKIPEDIA_MAXLAG', 5),

];
