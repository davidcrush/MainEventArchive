<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Promotion catalog metadata
    |--------------------------------------------------------------------------
    |
    | Used by seeders, Wikipedia import, and the public promotions index.
    | sort_order reserves slots for future promotions: wwe, aew, tna, wcw, ecw, roh.
    |
    */

    'wwe' => [
        'name' => 'World Wrestling Entertainment',
        'wikipedia_search_suffixes' => ['WWF', 'WWE'],
        'sort_order' => 1,
        'logo_path' => 'promotions/wwe.svg',
        'founded_year' => 1952,
        'active_from_year' => 1952,
        'active_to_year' => null,
        'is_active' => true,
        'headquarters' => 'Stamford, Connecticut, U.S.',
        'wikipedia_url' => 'https://en.wikipedia.org/wiki/WWE',
        'description' => 'WWE is the largest professional wrestling promotion in the world, producing Raw, SmackDown, NXT, and major pay-per-view events. Founded as Capitol Wrestling Corporation, it operated as the World Wrestling Federation before adopting the WWE name in 2002.',
    ],

    'aew' => [
        'name' => 'All Elite Wrestling',
        'wikipedia_search_suffixes' => ['AEW'],
        'sort_order' => 2,
        'logo_path' => 'promotions/aew.svg',
        'founded_year' => 2019,
        'active_from_year' => 2019,
        'active_to_year' => null,
        'is_active' => true,
        'headquarters' => 'Jacksonville, Florida, U.S.',
        'wikipedia_url' => 'https://en.wikipedia.org/wiki/All_Elite_Wrestling',
        'description' => 'All Elite Wrestling is a U.S. promotion founded in 2019, built around a roster of established independent and international stars. It produces weekly Dynamite and Collision broadcasts plus marquee pay-per-view events.',
    ],

    'tna' => [
        'name' => 'Total Nonstop Action',
        'wikipedia_search_suffixes' => ['TNA', 'Impact'],
        'sort_order' => 3,
        'logo_path' => 'promotions/tna.svg',
        'founded_year' => 2002,
        'active_from_year' => 2002,
        'active_to_year' => null,
        'is_active' => true,
        'headquarters' => 'Nashville, Tennessee, U.S.',
        'wikipedia_url' => 'https://en.wikipedia.org/wiki/Total_Nonstop_Action_Wrestling',
        'description' => 'Total Nonstop Action launched in 2002 as a national alternative to WWE and WCW, later competing as Impact Wrestling before returning to the TNA brand. Based in Nashville, it produces weekly TV and periodic PPV-style events.',
    ],

    'wcw' => [
        'name' => 'World Championship Wrestling',
        'wikipedia_search_suffixes' => ['WCW'],
        'sort_order' => 4,
        'logo_path' => 'promotions/wcw.svg',
        'founded_year' => 1988,
        'active_from_year' => 1988,
        'active_to_year' => 2001,
        'is_active' => false,
        'headquarters' => 'Atlanta, Georgia, U.S.',
        'wikipedia_url' => 'https://en.wikipedia.org/wiki/World_Championship_Wrestling',
        'description' => 'World Championship Wrestling was a major U.S. promotion that competed nationally with WWE through the Monday Night War. Based in Atlanta, it ran weekly Nitro and major PPVs until WWE acquired the company in 2001.',
    ],

    'ecw' => [
        'name' => 'Extreme Championship Wrestling',
        'wikipedia_search_suffixes' => ['ECW'],
        'sort_order' => 5,
        'logo_path' => 'promotions/ecw.svg',
        'founded_year' => 1992,
        'active_from_year' => 1992,
        'active_to_year' => 2001,
        'is_active' => false,
        'headquarters' => 'Philadelphia, Pennsylvania, U.S.',
        'wikipedia_url' => 'https://en.wikipedia.org/wiki/Extreme_Championship_Wrestling',
        'description' => 'Extreme Championship Wrestling was a Philadelphia-based promotion known for hardcore matches and a loyal cult audience. ECW ran weekly TV and PPVs until closing in 2001; WWE later revived the brand briefly for a separate ECW division.',
    ],

];
