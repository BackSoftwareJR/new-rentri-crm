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
    case StripeDisputeCreated = 'ecommerce.stripe_dispute_created';
    case BusinessKpiBreach = 'dashboard.business_kpi_breach';
    case VfuConsegnaAgenzia = 'vfu.consegna_agenzia';
    case VfuOperatoreAssegnato = 'vfu.operatore_assegnato';
    case VfuRottamato = 'vfu.rottamato';
    case SmontaggioCompletato = 'smontaggio.completato';
    case RentriInitialSyncFailed = 'rentri.initial_sync_failed';
    case GdprDeletionRequested = 'gdpr.deletion_requested';

    public function label(): string
    {
        return match ($this) {
            self::BonificaPericolosiCompletata => 'Bonifica pericolosi completata',
            self::MagazzinoSerbatoioSoglia => 'Alert soglia serbatoio',
            self::MudInvioTelematico => 'Invio telematico MUD',
            self::RentriDeadLetter => 'RENTRI dead-letter',
            self::RentriSlaBreach => 'RENTRI SLA fuori soglia',
            self::TrasportoGpsGeofence => 'Alert geofencing GPS trasporto',
            self::EcommerceStripeReconciliation => 'Riconciliazione pagamento Stripe',
            self::StripeDisputeCreated => 'Dispute Stripe ricevuta',
            self::BusinessKpiBreach => 'KPI business sotto soglia',
            self::VfuConsegnaAgenzia => 'VFU consegnato ad agenzia',
            self::VfuOperatoreAssegnato => 'VFU assegnato a operatore',
            self::VfuRottamato => 'VFU rottamato',
            self::SmontaggioCompletato => 'Smontaggio completato',
            self::RentriInitialSyncFailed => 'RENTRI sync iniziale fallito',
            self::GdprDeletionRequested => 'Richiesta cancellazione GDPR',
        };
    }

    public function module(): string
    {
        return match ($this) {
            self::BonificaPericolosiCompletata => 'bonifica',
            self::MagazzinoSerbatoioSoglia => 'magazzino',
            self::MudInvioTelematico => 'mud',
            self::RentriDeadLetter => 'rentri',
            self::RentriSlaBreach => 'rentri',
            self::TrasportoGpsGeofence => 'trasporti',
            self::EcommerceStripeReconciliation => 'ecommerce',
            self::StripeDisputeCreated => 'ecommerce',
            self::BusinessKpiBreach => 'dashboard',
            self::VfuConsegnaAgenzia => 'vfu',
            self::VfuOperatoreAssegnato => 'vfu',
            self::VfuRottamato => 'vfu',
            self::SmontaggioCompletato => 'operatore',
            self::RentriInitialSyncFailed => 'rentri',
            self::GdprDeletionRequested => 'settings',
        };
    }

    /** @return list<self> */
    public static function all(): array
    {
        return self::cases();
    }
}
