<?php

use App\Enums\NotificationEvent;

return [

    /*
    |--------------------------------------------------------------------------
    | Notification driver (stub)
    |--------------------------------------------------------------------------
    |
    | log  — scrive su canale dedicato (no SMTP reale)
    | mail — invia via Mail facade + log audit
    |
    */

    'driver' => env('NOTIFICATIONS_DRIVER', 'log'),

    /*
    |--------------------------------------------------------------------------
    | Live SMTP (Sprint 99)
    |--------------------------------------------------------------------------
    |
    | false — stub: nessun invio SMTP reale (log audit su canale notifications)
    | true  — live: Mail via mailer predefinito (MAIL_MAILER, tipicamente smtp)
    |
    */

    'live' => filter_var(env('NOTIFICATIONS_LIVE', false), FILTER_VALIDATE_BOOL),

    'queue' => (bool) env('NOTIFICATIONS_QUEUE', false),

    /*
    | SMTP volume limits (Sprint 107 — optional app-side caps)
    */
    'smtp_rate_limit_per_minute' => env('NOTIFICATIONS_SMTP_RATE_LIMIT_PER_MINUTE'),
    'smtp_daily_cap'             => env('NOTIFICATIONS_SMTP_DAILY_CAP'),

    'log_channel' => 'notifications',

    'default_recipient' => env('NOTIFICATIONS_RECIPIENT', env('BONIFICA_NOTIFY_EMAIL', 'segreteria@example.com')),

    'preferences_path' => storage_path('app/notification_preferences.json'),

    'events' => [
        NotificationEvent::BonificaPericolosiCompletata->value => [
            'enabled' => true,
        ],
        NotificationEvent::MagazzinoSerbatoioSoglia->value => [
            'enabled' => true,
        ],
        NotificationEvent::MudInvioTelematico->value => [
            'enabled' => true,
        ],
        NotificationEvent::RentriDeadLetter->value => [
            'enabled' => true,
        ],
        NotificationEvent::RentriSlaBreach->value => [
            'enabled' => true,
        ],
        NotificationEvent::TrasportoGpsGeofence->value => [
            'enabled' => true,
        ],
        NotificationEvent::EcommerceStripeReconciliation->value => [
            'enabled' => true,
        ],
        NotificationEvent::BusinessKpiBreach->value => [
            'enabled' => true,
        ],
    ],

];
