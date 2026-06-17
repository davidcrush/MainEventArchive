<?php

return [

    'api_endpoint' => 'https://prowrestling.fandom.com/api.php',

    'user_agent' => 'MainEventArchive/1.0 (https://github.com/main-event-archive; contact@davidcrush.com)',

    /*
     * Per-request retry policy. When the API returns 429 (rate limited) or 503,
     * the request is retried up to max_retries times. The Retry-After header is
     * honored when present; otherwise the delay grows exponentially from
     * retry_base_delay_ms. Fandom throttles more aggressively than Wikimedia,
     * so keep concurrency low when bulk importing.
     */
    'max_retries' => (int) env('FANDOM_MAX_RETRIES', 4),

    'retry_base_delay_ms' => (int) env('FANDOM_RETRY_BASE_DELAY_MS', 1000),

    /*
     * maxlag is a Wikimedia-specific parameter and is not honored by Fandom, so
     * it is disabled (0) here to avoid sending an unsupported query argument.
     */
    'maxlag' => (int) env('FANDOM_MAXLAG', 0),

];
