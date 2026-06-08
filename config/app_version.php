<?php

return [

    'version' => env('APP_VERSION', '2.0.0-sprint10'),

    'build' => env('APP_BUILD', (string) env('APP_ENV', 'local')),

    'env' => env('APP_ENV_LABEL', match (env('APP_ENV', 'local')) {
        'production' => 'production',
        'staging'    => 'staging',
        default      => 'sandbox',
    }),

];
