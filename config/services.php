<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    'rentri' => [
        'env'                   => env('RENTRI_ENV', 'sandbox'),
        'base_url_sandbox'    => env('RENTRI_BASE_URL_SANDBOX', 'https://demoapi.rentri.gov.it'),
        'base_url_production' => env('RENTRI_BASE_URL_PRODUCTION', 'https://api.rentri.gov.it'),
        'timeout'             => (int) env('RENTRI_HTTP_TIMEOUT', 30),
        'api_stub'            => filter_var(env('RENTRI_API_STUB', true), FILTER_VALIDATE_BOOL),
        'auth_mode'           => env('RENTRI_AUTH_MODE', 'mtls'),
        'verify_ssl'          => filter_var(env('RENTRI_VERIFY_SSL', true), FILTER_VALIDATE_BOOL),
        'integration_test'    => filter_var(env('RENTRI_INTEGRATION_TEST', false), FILTER_VALIDATE_BOOL),
        'sandbox_cert_path'   => env('RENTRI_SANDBOX_CERT_PATH'),
        'sandbox_cert_password' => env('RENTRI_SANDBOX_CERT_PASSWORD', ''),
        'production_integration_test' => filter_var(env('RENTRI_PRODUCTION_INTEGRATION_TEST', false), FILTER_VALIDATE_BOOL),
        'production_cert_path' => env('RENTRI_PRODUCTION_CERT_PATH'),
        'production_cert_password' => env('RENTRI_PRODUCTION_CERT_PASSWORD', ''),
        'fir_poll_max_attempts' => (int) env('RENTRI_FIR_POLL_MAX_ATTEMPTS', 15),
        'fir_poll_interval_ms'  => (int) env('RENTRI_FIR_POLL_INTERVAL_MS', 200),
        'fir_progressivo_max'   => (int) env('RENTRI_FIR_PROGRESSIVO_MAX', 9999),
        'registro_poll_max_attempts' => (int) env('RENTRI_REGISTRO_POLL_MAX_ATTEMPTS', 15),
        'registro_poll_interval_ms'  => (int) env('RENTRI_REGISTRO_POLL_INTERVAL_MS', 200),
        'xfir_poll_max_attempts'     => (int) env('RENTRI_XFIR_POLL_MAX_ATTEMPTS', 20),
        'xfir_poll_interval_ms'      => (int) env('RENTRI_XFIR_POLL_INTERVAL_MS', 300),
        'firma_stub'                 => filter_var(env('RENTRI_FIRMA_STUB', env('RENTRI_API_STUB', true)), FILTER_VALIDATE_BOOL),
        'xfir_schema_path'           => env('RENTRI_XFIR_SCHEMA_PATH', resource_path('schemas/rentri/xfir-v1.0.xsd')),
        'retry_enabled'              => filter_var(env('RENTRI_RETRY_ENABLED', true), FILTER_VALIDATE_BOOL),
        'retry_max_attempts'         => (int) env('RENTRI_RETRY_MAX_ATTEMPTS', 5),
        'retry_base_delay_seconds'   => (int) env('RENTRI_RETRY_BASE_DELAY_SECONDS', 60),
        'retry_max_delay_seconds'    => (int) env('RENTRI_RETRY_MAX_DELAY_SECONDS', 3600),
        'sla'                        => [
            'p95_latency_seconds'      => (int) env('RENTRI_SLA_P95_LATENCY_SECONDS', 120),
            'dead_letter_rate_percent' => (float) env('RENTRI_SLA_DEAD_LETTER_RATE_PERCENT', 5.0),
            'max_avg_retry_count'      => (float) env('RENTRI_SLA_MAX_AVG_RETRY_COUNT', 1.0),
        ],
    ],

    'bonifica' => [
        'notify_email' => env('BONIFICA_NOTIFY_EMAIL', 'segreteria@example.com'),
    ],

    'mud_telematico' => [
        'stub'               => filter_var(env('MUD_TELEMATICO_STUB', true), FILTER_VALIDATE_BOOL),
        'env'                => env('MUD_TELEMATICO_ENV', env('RENTRI_ENV', 'sandbox')),
        'base_url'           => env('MUD_TELEMATICO_BASE_URL'),
        'submit_path'        => env('MUD_TELEMATICO_SUBMIT_PATH'),
        'status_path'        => env('MUD_TELEMATICO_STATUS_PATH'),
        'result_path'        => env('MUD_TELEMATICO_RESULT_PATH'),
        'timeout'            => (int) env('MUD_TELEMATICO_TIMEOUT', 30),
        'poll_max_attempts'  => (int) env('MUD_TELEMATICO_POLL_MAX_ATTEMPTS', 15),
        'poll_interval_ms'   => (int) env('MUD_TELEMATICO_POLL_INTERVAL_MS', 200),
    ],

    'ecommerce' => [
        'payment_stub' => filter_var(env('ECOMMERCE_PAYMENT_STUB', true), FILTER_VALIDATE_BOOL),
    ],

    'stripe' => [
        'secret'                 => env('STRIPE_KEY'),
        'webhook_secret'         => env('STRIPE_WEBHOOK_SECRET'),
        'dispute_webhook_secret' => env('STRIPE_DISPUTE_WEBHOOK_SECRET'),
        'currency'               => env('STRIPE_CURRENCY', 'eur'),
        'live_mode'              => filter_var(env('STRIPE_LIVE_MODE', false), FILTER_VALIDATE_BOOL),
        'dispute_stub'           => filter_var(env('STRIPE_DISPUTE_STUB', true), FILTER_VALIDATE_BOOL),
        'reconciliation_days'    => (int) env('STRIPE_RECONCILIATION_DAYS', 30),
    ],

    'trasporto_gps' => [
        'stub'           => filter_var(env('TRASPORTO_GPS_STUB', true), FILTER_VALIDATE_BOOL),
        'provider_url'   => env('TRASPORTO_GPS_PROVIDER_URL', 'https://gps-provider.example.com/api/v1'),
        'api_key'        => env('TRASPORTO_GPS_API_KEY'),
        'positions_path' => env('TRASPORTO_GPS_POSITIONS_PATH', '/trasporti/{id}/position'),
        'timeout'        => (int) env('TRASPORTO_GPS_TIMEOUT', 15),
        'field_map'      => [
            'latitude'    => env('TRASPORTO_GPS_FIELD_LAT', 'latitude'),
            'longitude'   => env('TRASPORTO_GPS_FIELD_LNG', 'longitude'),
            'recorded_at' => env('TRASPORTO_GPS_FIELD_RECORDED_AT', 'recorded_at'),
            'speed_kmh'   => env('TRASPORTO_GPS_FIELD_SPEED', 'speed_kmh'),
        ],
        'geofence_enabled'          => filter_var(env('TRASPORTO_GPS_GEOFENCE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'geofence_radius_km'        => (float) env('TRASPORTO_GPS_GEOFENCE_RADIUS_KM', 50),
        'geofence_destination_lat'  => env('TRASPORTO_GPS_GEOFENCE_DEST_LAT'),
        'geofence_destination_lng'  => env('TRASPORTO_GPS_GEOFENCE_DEST_LNG'),
        'probe_transport_id'        => (int) env('TRASPORTO_GPS_PROBE_TRANSPORT_ID', 0),
    ],

];
