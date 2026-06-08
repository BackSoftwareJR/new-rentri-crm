<?php

namespace App\Enums;

enum NotificationEvent: string
{
    case BonificaPericolosiCompletata = 'bonifica.pericolosi_completata';
    case MagazzinoSerbatoioSoglia = 'magazzino.serbatoio_soglia';
    case MudInvioTelematico = 'mud.invio_telematico';
    case RentriDeadLetter = 'rentri.dead_letter';
    case RentriSlaBreach = 'rentri.sla_breach';
    case TrasportoGpsGeofence = 'trasporto.gps_geofence';
    case EcommerceStripeReconciliation = 'ecommerce.stripe_reconciliation';
    case BusinessKpiBreach = 'dashboard.business_kpi_breach';

    public function label(): string
    {
        return match ($this) {
            self::BonificaPericolosiCompletata => 'Bonifica pericolosi completata',
            self::MagazzinoSerbatoioSoglia     => 'Alert soglia serbatoio',
            self::MudInvioTelematico           => 'Invio telematico MUD',
            self::RentriDeadLetter             => 'RENTRI dead-letter',
            self::RentriSlaBreach              => 'RENTRI SLA fuori soglia',
            self::TrasportoGpsGeofence         => 'Alert geofencing GPS trasporto',
            self::EcommerceStripeReconciliation => 'Riconciliazione pagamento Stripe',
            self::BusinessKpiBreach            => 'KPI business sotto soglia',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::BonificaPericolosiCompletata => 'bonifica',
            self::MagazzinoSerbatoioSoglia     => 'magazzino',
            self::MudInvioTelematico           => 'mud',
            self::RentriDeadLetter             => 'rentri',
            self::RentriSlaBreach              => 'rentri',
            self::TrasportoGpsGeofence         => 'trasporti',
            self::EcommerceStripeReconciliation => 'ecommerce',
            self::BusinessKpiBreach            => 'dashboard',
        };
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
