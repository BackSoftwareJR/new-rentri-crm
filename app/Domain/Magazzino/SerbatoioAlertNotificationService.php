<?php

namespace App\Domain\Magazzino;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\SerbatoioSogliaAlertMail;
use App\Models\CodiceCer;

class SerbatoioAlertNotificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    /**
     * @param  array<string, mixed>  $serbatoio
     */
    public function notifyThreshold(array $serbatoio): void
    {
        if (! in_array($serbatoio['stato'] ?? '', ['attenzione', 'superata'], true)) {
            return;
        }

        $statoLabel = match ($serbatoio['stato']) {
            'superata'   => 'Soglia superata',
            'attenzione' => 'Attenzione',
            default      => 'Regolare',
        };

        $this->notifications->dispatch(
            NotificationEvent::MagazzinoSerbatoioSoglia,
            new SerbatoioSogliaAlertMail($serbatoio, $statoLabel),
            context: [
                'codice_cer'  => $serbatoio['codice'] ?? null,
                'stato'       => $serbatoio['stato'],
                'percentuale' => $serbatoio['percentuale'] ?? null,
                'giacenza_kg' => $serbatoio['quantita_attuale_kg'] ?? null,
                'limite_kg'   => $serbatoio['limite_kg'] ?? null,
            ],
        );
    }

    public function maybeNotifyAfterCarico(CodiceCer $cer): void
    {
        $cer->loadMissing('magazzino');

        $giacenza = (float) ($cer->magazzino?->quantita_attuale_kg ?? 0);
        $limite = $cer->limite_kg !== null ? (float) $cer->limite_kg : null;
        $percentuale = ($limite !== null && $limite > 0)
            ? round(($giacenza / $limite) * 100, 1)
            : null;

        $stato = match (true) {
            $percentuale === null => 'regolare',
            $percentuale > 100   => 'superata',
            $percentuale >= MagazzinoService::SOGLIA_ATTENZIONE_PCT => 'attenzione',
            default               => 'regolare',
        };

        $this->notifyThreshold([
            'codice'              => $cer->codice,
            'stato'               => $stato,
            'percentuale'         => $percentuale,
            'quantita_attuale_kg' => $giacenza,
            'limite_kg'           => $limite,
        ]);
    }
}
