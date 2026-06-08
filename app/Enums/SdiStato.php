<?php

namespace App\Enums;

enum SdiStato: string
{
    case DaInviare = 'da_inviare';
    case Inviata = 'inviata';
    case Consegnata = 'consegnata';
    case Scartata = 'scartata';
    case Accettata = 'accettata';

    public function label(): string
    {
        return match ($this) {
            self::DaInviare  => 'Da inviare',
            self::Inviata    => 'Inviata',
            self::Consegnata => 'Consegnata',
            self::Scartata   => 'Scartata',
            self::Accettata  => 'Accettata',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::DaInviare  => '#6b7280',
            self::Inviata    => '#2563eb',
            self::Consegnata => '#16a34a',
            self::Scartata   => '#dc2626',
            self::Accettata  => '#16a34a',
        };
    }
}
