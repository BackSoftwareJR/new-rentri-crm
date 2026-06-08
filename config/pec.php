<?php

return [

    'enabled' => filter_var(env('PEC_ENABLED', false), FILTER_VALIDATE_BOOL),

    'host' => env('PEC_HOST', 'smtp.pec-provider.it'),

    'port' => (int) env('PEC_PORT', 465),

    'username' => env('PEC_USERNAME'),

    'password' => env('PEC_PASSWORD'),

    'from' => [
        'address' => env('PEC_FROM_ADDRESS', env('PEC_USERNAME')),
        'name' => env('PEC_FROM_NAME', env('APP_NAME', 'Laravel')),
    ],

];
