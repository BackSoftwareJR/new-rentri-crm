<?php

namespace App\Enums;

enum FirStato: string
{
    case Bozza = 'bozza';
    case Vidimato = 'vidimato';
    case Firmato = 'firmato';
    case Trasmesso = 'trasmesso';
    case Annullato = 'annullato';
}
