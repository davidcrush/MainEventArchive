<?php

return [

    'netflix' => [
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
