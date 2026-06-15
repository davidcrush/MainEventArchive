<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Promotion catalog metadata
    |--------------------------------------------------------------------------
    |
    | Used by seeders and Wikipedia import for promotion-specific behavior.
    |
    */

    'wcw' => [
        'name' => 'World Championship Wrestling',
        'wikipedia_search_suffixes' => ['WCW'],
    ],

    'wwe' => [
        'name' => 'World Wrestling Entertainment',
        'wikipedia_search_suffixes' => ['WWF', 'WWE'],
    ],

];
