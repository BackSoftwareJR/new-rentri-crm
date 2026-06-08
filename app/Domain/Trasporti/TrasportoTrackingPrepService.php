<?php

namespace App\Domain\Trasporti;

use App\Enums\TrasportoStato;
use App\Models\Trasporto;
use Illuminate\Support\Facades\Log;

class TrasportoTrackingPrepService
{
    public function __construct(
        private TrasportoTrackingService $tracking,
        private TrasportoGpsRuntimeModeService $gpsRuntime,
        private TrasportoGpsTrackingService $gpsTracking,
    ) {}

    public function prepLabel(): string
    {
        return $this->gpsRuntime->prepLabel();
    }

    public function isPrepActive(Trasporto $trasporto): bool
    {
        return $this->tracking->isTrackingAvailable($trasporto);
    }

    public function etaStub(Trasporto $trasporto): ?string
    {
        if ($trasporto->stato !== TrasportoStato::InTransito) {
            return null;
        }

        return $trasporto->updated_at->copy()->addHours(4)->format('d/m/Y H:i');
    }

    /**
     * @return list<array{key: string, label: string, at: string, status: string}>
     */
    public function timeline(Trasporto $trasporto): array
    {
        if ($this->isPrepActive($trasporto)) {
            Log::info($this->gpsRuntime->isStub() ? 'trasporto.tracking.stub' : 'trasporto.tracking.live', [
                'trasporto_id' => $trasporto->id,
                'stato'        => $trasporto->stato->value,
                'eta_stub'     => $this->etaStub($trasporto),
                'gps_mode'     => $this->gpsRuntime->modeLabel(),
            ]);
        }

        $gpsLabel = $this->gpsRuntime->isStub() ? 'GPS stub attivo' : 'GPS live attivo';
        $gpsKey = $this->gpsRuntime->isStub() ? 'gps_stub' : 'gps_live';

        return match ($trasporto->stato) {
            TrasportoStato::InPreparazione => [
                $this->event('partenza_programmata', 'Partenza programmata', $trasporto->updated_at, 'pending'),
            ],
            TrasportoStato::InTransito => [
                $this->event('partenza', 'Partenza registrata', $trasporto->updated_at->copy()->subHour(), 'done'),
                $this->event($gpsKey, $gpsLabel, $trasporto->gps_tracked_at ?? $trasporto->updated_at, 'active'),
                $this->event('eta', 'ETA stimata: '.$this->etaStub($trasporto), $trasporto->updated_at, 'pending'),
                $this->event('arrivo_previsto', 'Arrivo previsto in impianto', $trasporto->updated_at->copy()->addHours(4), 'pending'),
            ],
            TrasportoStato::Completato => [
                $this->event('partenza', 'Partenza registrata', $trasporto->updated_at->copy()->subHours(5), 'done'),
                $this->event('transito', 'Transito completato', $trasporto->updated_at->copy()->subHour(), 'done'),
                $this->event('arrivo', 'Arrivo registrato', $trasporto->updated_at, 'done'),
            ],
            default => [],
        };
    }

    /**
     * @return array{key: string, label: string, at: string, status: string}
     */
    private function event(string $key, string $label, \DateTimeInterface $at, string $status): array
    {
        return [
            'key'    => $key,
            'label'  => $label,
            'at'     => $at->format('d/m/Y H:i'),
            'status' => $status,
        ];
    }
}
