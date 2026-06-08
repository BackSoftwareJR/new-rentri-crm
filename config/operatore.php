<?php

return [

    'pwa' => [
        'name'             => env('OPERATORE_PWA_NAME', 'RENTRI Operatore'),
        'short_name'       => env('OPERATORE_PWA_SHORT_NAME', 'Operatore'),
        'description'      => 'App operatore — bonifica VFU, ricambi e vetrina.',
        'theme_color'      => env('OPERATORE_PWA_THEME_COLOR', '#F2F2F7'),
        'background_color' => env('OPERATORE_PWA_BACKGROUND_COLOR', '#FFFFFF'),
        'display'          => 'standalone',
        'start_url'        => '/operatore',
        'scope'            => '/operatore/',
        'cache_version'    => env('OPERATORE_PWA_CACHE_VERSION', 'v1'),
    ],

];
