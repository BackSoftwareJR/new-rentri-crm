<?php

return [

    'horizon' => [
        'min_workers_production'      => (int) env('HORIZON_MIN_WORKERS', 3),
        'failed_jobs_warn_threshold'  => (int) env('HORIZON_FAILED_JOBS_WARN', 0),
        'retry_warn_threshold'        => (int) env('HORIZON_RETRY_WARN_THRESHOLD', 10),
    ],

    'backup' => [
        'schedule_enabled' => filter_var(env('DB_BACKUP_SCHEDULE_ENABLED', false), FILTER_VALIDATE_BOOL),
        'cron'             => env('DB_BACKUP_CRON', '0 2 * * *'),
        'retention_days'   => (int) env('DB_BACKUP_RETENTION_DAYS', 30),
        'storage_path'     => env('DB_BACKUP_STORAGE_PATH'),
        'last_drill_at'    => env('DB_BACKUP_LAST_DRILL_AT'),
    ],

    'ha' => [
        'min_app_instances'        => (int) env('HA_MIN_APP_INSTANCES', 2),
        'session_redis_required'   => filter_var(env('HA_SESSION_REDIS_REQUIRED', false), FILTER_VALIDATE_BOOL),
        'rpo_minutes'              => (int) env('HA_RPO_MINUTES', 60),
        'rto_minutes'              => (int) env('HA_RTO_MINUTES', 240),
        'quarterly_drill_months'   => (int) env('HA_BACKUP_DRILL_INTERVAL_MONTHS', 3),
        'primary_app_url'          => env('HA_PRIMARY_APP_URL'),
        'secondary_app_url'        => env('HA_SECONDARY_APP_URL'),
        'last_failover_drill_at'   => env('HA_LAST_FAILOVER_DRILL_AT'),
        'failover_drill_months'    => (int) env('HA_FAILOVER_DRILL_INTERVAL_MONTHS', 6),
    ],

];
