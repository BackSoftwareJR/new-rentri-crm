<?php

namespace App\Enums;

enum SvuotamentoStato: string
{
    case Richiesto = 'richiesto';
    case Completato = 'completato';
    case Annullato = 'annullato';
}
