<?php

namespace App\Enums;

enum RegistroMovimentoTipo: string
{
    case Carico = 'carico';
    case Scarico = 'scarico';
}
