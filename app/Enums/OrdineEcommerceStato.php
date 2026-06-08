<?php

namespace App\Enums;

enum OrdineEcommerceStato: string
{
    case Bozza = 'bozza';
    case PagamentoInAttesa = 'pagamento_in_attesa';
    case Confermato = 'confermato';
    case Annullato = 'annullato';
}
