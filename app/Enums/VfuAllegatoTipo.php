<?php

namespace App\Enums;

enum VfuAllegatoTipo: string
{
    case Contratto = 'contratto';
    case Foto = 'foto';
    case Verbale = 'verbale';
    case Altro = 'altro';

    public function label(): string
    {
        return match ($this) {
            self::Contratto => 'Contratto',
            self::Foto => 'Foto',
            self::Verbale => 'Verbale',
            self::Altro => 'Altro',
        };
    }
}
