<?php

return [

    /*
    |--------------------------------------------------------------------------
    | WAF deployment mode (Sprint 105)
    |--------------------------------------------------------------------------
    |
    | off     — nessuna regola edge attiva (locale/dev default)
    | monitor — count-only / log SIEM, nessun block (rollout fase 1)
    | block   — regole OWASP attive con deny (rollout fase 2)
    |
    */

    'mode' => env('WAF_MODE', 'off'),

    'provider' => env('WAF_PROVIDER', 'aws'), // aws | cloudflare | other

    'siem_log_group' => env('WAF_SIEM_LOG_GROUP'),

    'monitor_hours_before_block' => (int) env('WAF_MONITOR_HOURS_BEFORE_BLOCK', 48),

];
