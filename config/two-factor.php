<?php

return [

    /*
    |--------------------------------------------------------------------------
    | 2FA opt-in (Sprint 67)
    |--------------------------------------------------------------------------
    |
    | Quando true, l'utente può abilitare TOTP volontariamente.
    | Nessun middleware di enforcement globale in questa slice.
    |
    */

    'optional' => (bool) env('TWO_FACTOR_OPTIONAL', true),

    'issuer' => env('TWO_FACTOR_ISSUER', env('APP_NAME', 'ERP VFU')),

    'challenge_throttle' => '5,1',

    /*
    |--------------------------------------------------------------------------
    | Enforcement admin/segreteria (Sprint 97)
    |--------------------------------------------------------------------------
    |
    | Quando true, utenti admin e segreteria devono avere 2FA confermato
    | per accedere alle route segreteria/admin (operatore ed editor esclusi).
    | Grace period opzionale: TWO_FACTOR_ENFORCE_GRACE_UNTIL (ISO datetime).
    |
    */

    'enforce_admin_segreteria' => filter_var(
        env('TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA', false),
        FILTER_VALIDATE_BOOL,
    ),

    'enforce_grace_until' => env('TWO_FACTOR_ENFORCE_GRACE_UNTIL'),

];
