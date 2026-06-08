<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Persistenza consultabile (application_logs)
    |--------------------------------------------------------------------------
    */

    'persist_to_database' => env('APP_LOG_PERSIST_DB', true),

    'retention_days' => (int) env('APP_LOG_RETENTION_DAYS', 90),

    'export_max_days' => (int) env('APP_LOG_EXPORT_MAX_DAYS', 30),

    /*
    |--------------------------------------------------------------------------
    | Moduli applicativi (filtri UI + StructuredLogService)
    |--------------------------------------------------------------------------
    */

    'modules' => [
        'rentri',
        'ecommerce',
        'gps',
        'stripe',
        'security',
        'business',
        'operatore',
        'integration',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mapping modulo → canale Monolog dedicato
    |--------------------------------------------------------------------------
    */

    'module_channels' => [
        'rentri'       => 'rentri',
        'ecommerce'    => 'integration',
        'gps'          => 'integration',
        'stripe'       => 'integration',
        'security'     => 'security',
        'business'     => 'business',
        'operatore'    => 'business',
        'integration'  => 'integration',
    ],

    /*
    |--------------------------------------------------------------------------
    | Livelli ammessi in UI
    |--------------------------------------------------------------------------
    */

    'levels' => ['debug', 'info', 'notice', 'warning', 'error', 'critical', 'alert', 'emergency'],

];
