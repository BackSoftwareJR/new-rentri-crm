<?php

namespace App\Enums;

enum VfuStato: string
{
    case Bozza = 'bozza';
    case InAccettazione = 'in_accettazione';
    case Accettato = 'accettato';
    case AttesaBonifica = 'attesa_bonifica';
    case InBonifica = 'in_bonifica';
    case Bonificato = 'bonificato';
    case InSmontaggio = 'in_smontaggio';
    case Smontato = 'smontato';
    case InviatoAgenzia = 'inviato_agenzia';
    case Rottamato = 'rottamato';
    case Annullato = 'annullato';

    public function label(): string
    {
        return match ($this) {
            self::Bozza => 'Bozza',
            self::InAccettazione => 'In accettazione',
            self::Accettato => 'Accettato',
            self::AttesaBonifica => 'Attesa bonifica',
            self::InBonifica => 'In bonifica',
            self::Bonificato => 'Bonificato',
            self::InSmontaggio => 'In smontaggio',
            self::Smontato => 'Smontato',
            self::InviatoAgenzia => 'Inviato ad agenzia',
            self::Rottamato => 'Rottamato',
            self::Annullato => 'Annullato',
        };
    }

    public function badgeStato(): string
    {
        return match ($this) {
            self::Bozza, self::InAccettazione => 'muted',
            self::Accettato, self::AttesaBonifica => 'info',
            self::InBonifica => 'warning',
            self::Bonificato => 'success',
            self::InSmontaggio => 'warning',
            self::Smontato, self::Rottamato => 'success',
            self::InviatoAgenzia => 'info',
            self::Annullato => 'danger',
        };
    }
}
