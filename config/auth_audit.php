<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Public route names (explicit allowlist)
    |--------------------------------------------------------------------------
    |
    | Routes that are intentionally public but not covered by the middleware
    | groups below (webhooks, storage, health checks, etc.).
    |
    */

    'public_route_names' => [
        'webhooks.stripe.ecommerce',
        'storage.local',
        'api.version',
        'health.check',
    ],

    /*
    |--------------------------------------------------------------------------
    | Public middleware indicators
    |--------------------------------------------------------------------------
    |
    | Any named route whose middleware stack includes one of these aliases
    | (without auth) is treated as intentionally public by EmptyStateRouteAuditTest.
    |
    */

    'public_middleware' => [
        'guest',
        'shop.enabled',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skipped route name prefixes
    |--------------------------------------------------------------------------
    */

    'skip_route_name_prefixes' => [
        'livewire.',
        'default-livewire.',
    ],

    /*
    |--------------------------------------------------------------------------
    | Skipped URI prefixes
    |--------------------------------------------------------------------------
    */

    'skip_uri_prefixes' => [
        'up',
        'horizon',
        'storage/',
    ],

];
