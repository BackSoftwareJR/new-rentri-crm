<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Demo mode CRM
    |--------------------------------------------------------------------------
    |
    | APP_DEMO_MODE=true isola i dati RENTRI-critical con colonna is_demo.
    | Deploy demo consigliato: istanza/env separato (.env.demo).
    |
    | Palestra operativa (Sprint 46): toggle sessione su istanza condivisa
    | quando ALLOW_SESSION_DEMO=true (mai su production senza flag esplicito).
    |
    */

    'enabled' => filter_var(env('APP_DEMO_MODE', false), FILTER_VALIDATE_BOOL),

    'allow_session_toggle' => filter_var(env('ALLOW_SESSION_DEMO', false), FILTER_VALIDATE_BOOL),

    'session' => [
        'key' => 'demo_mode_active',
    ],

    'rentri' => [
        'force_sandbox_api' => filter_var(env('RENTRI_DEMO_FORCE_SANDBOX', true), FILTER_VALIDATE_BOOL),
        'offline_no_http'   => filter_var(env('RENTRI_DEMO_NO_HTTP', false), FILTER_VALIDATE_BOOL),
    ],

    /*
    | Default vuoti per preset sandbox UI — valori reali inseriti dall'operatore.
    | MASE usa certificato PKCS#12 upload, non API key nel repository.
    */
    'rentri_preset' => [
        'cf'              => env('RENTRI_DEMO_PRESET_CF', ''),
        'cf_operatore'    => env('RENTRI_DEMO_PRESET_CF_OPERATORE', ''),
        'piva'            => env('RENTRI_DEMO_PRESET_PIVA', ''),
        'ragione_sociale' => env('RENTRI_DEMO_PRESET_RAGIONE_SOCIALE', ''),
        'num_iscr_sito'   => env('RENTRI_DEMO_PRESET_NUM_ISCR_SITO', ''),
    ],

    /*
    | Profili operatore sandbox per formazione multi-sede (Sprint 48).
    | Chiavi selezionabili da UI Impostazioni RENTRI in palestra operativa.
    */
    'operators' => [
        'default' => [
            'label'           => 'Sede principale — demo standard',
            'cf'              => '00000000000',
            'cf_operatore'    => 'RSSMRA80A01H501Z',
            'piva'            => '00000000000',
            'ragione_sociale' => 'Palestra operativa RENTRI',
            'num_iscr_sito'   => 'DEMO-SITE-001',
        ],
        'sede_nord' => [
            'label'           => 'Sede Nord — formazione',
            'cf'              => '11111111111',
            'cf_operatore'    => 'VRDLGU85M01F205X',
            'piva'            => '11111111111',
            'ragione_sociale' => 'Autodemolizioni Nord Demo',
            'num_iscr_sito'   => 'DEMO-SITE-NORD-001',
        ],
        'sede_sud' => [
            'label'           => 'Sede Sud — formazione',
            'cf'              => '22222222222',
            'cf_operatore'    => 'BNCMRA90A01H501U',
            'piva'            => '22222222222',
            'ragione_sociale' => 'Autodemolizioni Sud Demo',
            'num_iscr_sito'   => 'DEMO-SITE-SUD-001',
        ],
    ],

];
