<?php

namespace App\Enums;

enum MudStato: string
{
    case Bozza = 'bozza';
    case Completata = 'completata';
    case Inviata = 'inviata';
}
