<?php

return [

    'netflix' => [
        // Curated /watch/{id} deep links. Disabled by default after Netflix's Jan 2026 WWE
        // catalog migration invalidated pre-migration title IDs — use search fallback until re-import.
        'deep_links_enabled' => env('NETFLIX_DEEP_LINKS_ENABLED', false),
        'wwe_ppv_search_enabled' => env('NETFLIX_WWE_PPV_SEARCH_ENABLED', true),
        'search_url_template' => env(
            'NETFLIX_SEARCH_URL_TEMPLATE',
            'https://www.netflix.com/search?q=%s',
        ),
        'watch_url_template' => env(
            'NETFLIX_WATCH_URL_TEMPLATE',
            'https://www.netflix.com/watch/%s',
        ),
    ],

];
