<?php

return [

    'kpi_cache' => [

        'enabled' => (bool) env('KPI_CACHE_ENABLED', true),

        /*
        | Cache store — null uses default (array in phpunit.xml). Use `redis` in production.
        */
        'store' => env('KPI_CACHE_STORE'),

        'ttl_seconds' => (int) env('KPI_CACHE_TTL', 300),

    ],

    /*
    | Soglie minime per KPI business v2/v3 (periodo 7/30 gg).
    | alert = sotto soglia critica; warn = sotto soglia attenzione.
    | Override env: KPI_BUSINESS_*_WARN / KPI_BUSINESS_*_ALERT
    */
    'business_kpi' => [
        'thresholds' => [
            'ordini_confermati' => [
                'warn'  => (int) env('KPI_BUSINESS_ORDINI_WARN', 5),
                'alert' => (int) env('KPI_BUSINESS_ORDINI_ALERT', 1),
            ],
            'vfu_accettate' => [
                'warn'  => (int) env('KPI_BUSINESS_VFU_WARN', 8),
                'alert' => (int) env('KPI_BUSINESS_VFU_ALERT', 2),
            ],
            'magazzino_kg' => [
                'warn'  => (int) env('KPI_BUSINESS_MAGAZZINO_WARN', 500),
                'alert' => (int) env('KPI_BUSINESS_MAGAZZINO_ALERT', 100),
            ],
            'revenue_eur' => [
                'warn'  => (int) env('KPI_BUSINESS_REVENUE_WARN', 500),
                'alert' => (int) env('KPI_BUSINESS_REVENUE_ALERT', 100),
            ],
        ],
        'alert' => [
            'enabled'         => filter_var(env('KPI_BUSINESS_ALERT_ENABLED', true), FILTER_VALIDATE_BOOL),
            'period_default'  => env('KPI_BUSINESS_ALERT_PERIOD', 'last_7_days'),
        ],
    ],

];
