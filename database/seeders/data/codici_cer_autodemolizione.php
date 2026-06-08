<?php

/**
 * Codici CER tipici per autodemolizione / bonifica VFU.
 * Allineati ai mock del CRM legacy (16.01.04*, 13.02.08*, ecc.).
 */
return [
    [
        'codice' => '16.01.04*',
        'descrizione' => 'Veicoli fuori uso contenenti fluidi o altri componenti pericolosi',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.01.04',
        'descrizione' => 'Veicoli fuori uso privi di fluidi e componenti pericolosi',
        'categoria' => 'altro',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.01.07*',
        'descrizione' => 'Filtri dell\'olio usati',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => 500.00,
        'attivo' => true,
    ],
    [
        'codice' => '16.01.03*',
        'descrizione' => 'Filtri dell\'olio usati (non contaminati da sostanze pericolose)',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '13.02.08*',
        'descrizione' => 'Oli minerali non clorati per motori, ingranaggi e lubrificazione usati',
        'categoria' => 'pericoloso',
        'um' => 'L',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '13.02.05*',
        'descrizione' => 'Miscela di oli minerali non clorati e sintetici usati',
        'categoria' => 'pericoloso',
        'um' => 'L',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.06.01*',
        'descrizione' => 'Batterie al piombo',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.06.02*',
        'descrizione' => 'Batterie al nichel-cadmio',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.06.03*',
        'descrizione' => 'Batterie al mercurio',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '16.01.06',
        'descrizione' => 'Componenti di veicoli fuori uso privi di fluidi e componenti pericolosi',
        'categoria' => 'altro',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '15.01.02',
        'descrizione' => 'Imballaggi in plastica',
        'categoria' => 'altro',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
    [
        'codice' => '14.06.01*',
        'descrizione' => 'Refrigeranti fluorati',
        'categoria' => 'pericoloso',
        'um' => 'kg',
        'limite_kg' => null,
        'attivo' => true,
    ],
];
