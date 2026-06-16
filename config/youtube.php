<?php

return [

    'api_key' => env('YOUTUBE_API_KEY'),

    'playlists' => [
        'wcw_ppv' => env('YOUTUBE_WCW_PPV_PLAYLIST_ID', 'PLqeI_ua6LQHFBDB6i0rJTw4Z2SRUr_Fb4'),
        'wcw_clash' => env('YOUTUBE_WCW_CLASH_PLAYLIST_ID', 'PLqeI_ua6LQHHSKExkeTqIq2hhqCtFr9jf'),
        'wcw_nitro' => env('YOUTUBE_WCW_NITRO_PLAYLIST_ID', 'PLqeI_ua6LQHFR45AlUQEPNjfig3o0W9zx'),
        'wwe_ppv' => env('YOUTUBE_WWE_PPV_PLAYLIST_ID', 'PLfalIYDZtGGDBmKl0slVTSSHEbbi1iTRu'),
    ],

];
