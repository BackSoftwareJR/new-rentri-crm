<?php

namespace App\Http\Livewire\Segreteria;

use App\Domain\Rentri\RentriProdReadinessService;
use App\Domain\Rentri\RentriProductionSwitchService;
use App\Domain\Rentri\RentriRegistroAuditExportService;
use App\Domain\Rentri\RentriRegistroConformitaValidator;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Domain\Rentri\RentriSlaAlertService;
use App\Domain\Rentri\RentriSlaMetricsService;
use App\Models\RentriSetting;
use App\Models\RentriTransmissione;
use App\Services\Rentri\Contracts\RentriRegistryServiceInterface;
use App\Services\Rentri\Dto\TransmissionPayload;
use App\Services\Rentri\Exceptions\RentriRegistroConformitaException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('RENTRI — Trasmissione registro')]
class Rentri extends SegreteriaPage
{
    use AuthorizesRequests;

    public string $periodo_da = '';

    public string $periodo_a = '';

    public int $slaPeriodDays = 7;

    public function mount(): void
    {
        $this->authorize('viewAny', RentriTransmissione::class);

        $this->periodo_da = now()->startOfMonth()->toDateString();
        $this->periodo_a = now()->toDateString();
    }

    public function trasmetti(RentriRegistryServiceInterface $registry): void
    {
        $this->authorize('create', RentriTransmissione::class);

        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        $rateKey = 'rentri-transmit:'.$userId;
        if (RateLimiter::tooManyAttempts($rateKey, 3)) {
            session()->flash('error', 'Troppe trasmissioni in poco tempo. Attendi un minuto e riprova.');

            return;
        }

        RateLimiter::hit($rateKey, 60);

        $validated = $this->validate([
            'periodo_da' => ['required', 'date'],
            'periodo_a'  => ['required', 'date', 'after_or_equal:periodo_da'],
        ]);

        $payload = $registry->buildTransmissionPayload(
            Carbon::parse($validated['periodo_da']),
            Carbon::parse($validated['periodo_a']),
        );

        if ($payload->metadata['count'] === 0) {
            $this->addError('periodo_da', 'Nessun movimento non trasmesso nel periodo selezionato.');

            return;
        }

        try {
            $transmissione = $registry->transmit($payload);
        } catch (RentriRegistroConformitaException $e) {
            foreach ($e->errors as $error) {
                $this->addError('periodo_da', $error);
            }

            return;
        }

        session()->flash('success', $this->trasmissioneSuccessMessage($transmissione, $payload->metadata['count']));
    }

    public function exportAuditJson(int $transmissioneId, RentriRegistroAuditExportService $export): StreamedResponse
    {
        $transmissione = RentriTransmissione::query()->findOrFail($transmissioneId);
        $this->authorize('view', $transmissione);

        return $export->exportJson($transmissione);
    }

    public function exportAuditCsv(int $transmissioneId, RentriRegistroAuditExportService $export): StreamedResponse
    {
        $transmissione = RentriTransmissione::query()->findOrFail($transmissioneId);
        $this->authorize('view', $transmissione);

        return $export->exportCsv($transmissione);
    }

    /**
     * @return non-empty-string
     */
    protected function trasmissioneSuccessMessage(RentriTransmissione $transmissione, int $movimentiCount): string
    {
        $apiMode = $transmissione->response_json['api_mode']
            ?? app(RentriRuntimeModeService::class)->apiModeLabel();
        $modeLabel = app(RentriRuntimeModeService::class)->apiModeDisplayLabelFromApiMode($apiMode);
        $protocollo = $transmissione->response_json['protocollo'] ?? '—';

        return sprintf(
            'Trasmissione registro completata (%s): %d movimenti, protocollo %s.',
            $modeLabel,
            $movimentiCount,
            $protocollo,
        );
    }

    public function render(
        RentriRegistryServiceInterface $registry,
        RentriRegistroConformitaValidator $conformitaValidator,
        RentriProdReadinessService $prodReadiness,
        RentriProductionSwitchService $productionSwitch,
        RentriRuntimeModeService $runtimeMode,
        RentriSlaMetricsService $slaMetrics,
        RentriSlaAlertService $slaAlerts,
    ): View {
        $payload = $this->resolvePreviewPayload($registry);
        $settings = RentriSetting::instance();
        $checklist = $payload ? $conformitaValidator->checklist($payload, $settings) : [];
        $conforme = $payload ? $conformitaValidator->isConforme($payload, $settings) : false;

        $storico = RentriTransmissione::query()
            ->withCount('movimenti')
            ->orderByDesc('trasmesso_at')
            ->orderByDesc('id')
            ->limit(25)
            ->get();

        return $this->segreteriaView(
            'livewire.segreteria.rentri',
            [
                'payload'    => $payload,
                'checklist'  => $checklist,
                'conforme'   => $conforme,
                'storico'    => $storico,
                'showRentriProdStubBanner' => $prodReadiness->shouldShowProdStubBanner(),
                'productionSwitchSummary'  => $productionSwitch->summary($settings),
                'productionSwitchReady'    => $productionSwitch->canSwitchToProduction($settings),
                'productionActive'         => $productionSwitch->isProductionActive($settings),
                'rentriApiModeLabel'       => $runtimeMode->apiModeDisplayLabel($settings),
                'slaDashboard'             => $slaMetrics->dashboard($this->slaPeriodDays),
                'slaMetrics'               => $slaMetrics,
                'slaLastCheck'             => $slaAlerts->lastCheck(),
                'slaRecentBreaches'        => $slaAlerts->recentBreaches(5),
            ],
            'rentri',
            'RENTRI',
        );
    }

    private function resolvePreviewPayload(RentriRegistryServiceInterface $registry): ?TransmissionPayload
    {
        if ($this->periodo_da === '' || $this->periodo_a === '') {
            return null;
        }

        try {
            $da = Carbon::parse($this->periodo_da);
            $a = Carbon::parse($this->periodo_a);

            if ($a->lt($da)) {
                return null;
            }

            return $registry->buildTransmissionPayload($da, $a);
        } catch (\Throwable) {
            return null;
        }
    }
}
