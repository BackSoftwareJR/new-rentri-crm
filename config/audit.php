<?php

return [

    'export' => [

        /*
        | Disk for audit CSV exports — `audit_exports` (local) or `s3`.
        */
        'disk' => env('AUDIT_EXPORT_DISK', 'audit_exports'),

        /*
        | Days to retain export files before purge.
        */
        'retention_days' => (int) env('AUDIT_EXPORT_RETENTION_DAYS', 90),

        /*
        | Presigned / signed download URL TTL (minutes).
        */
        'presigned_ttl_minutes' => (int) env('AUDIT_EXPORT_PRESIGNED_TTL', 1440),

    ],

];
