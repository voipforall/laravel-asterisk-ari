<?php

return [

    'host' => env('ASTERISK_ARI_HOST', '127.0.0.1'),

    'port' => env('ASTERISK_ARI_PORT', 8088),

    'user' => env('ASTERISK_ARI_USER', 'asterisk'),

    'password' => env('ASTERISK_ARI_PASSWORD'),

    'app' => env('ASTERISK_ARI_APP', 'laravel'),

    'scheme' => env('ASTERISK_ARI_SCHEME', 'http'),

    'ws_scheme' => env('ASTERISK_ARI_WS_SCHEME', 'ws'),

    'timeout' => env('ASTERISK_ARI_TIMEOUT', 10),

];
