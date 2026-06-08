<?php

namespace App\Enums;

enum TrasportoStato: string
{
    case Bozza = 'bozza';
    case InPreparazione = 'in_preparazione';
    case InTransito = 'in_transito';
    case Completato = 'completato';
    case Annullato = 'annullato';
}
