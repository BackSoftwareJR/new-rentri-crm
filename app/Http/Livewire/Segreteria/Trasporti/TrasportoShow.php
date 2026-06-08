<?php

namespace App\Http\Livewire\Segreteria\Trasporti;

use App\Domain\Fir\FirBloccoService;
use App\Domain\Trasporti\TrasportoGpsTrackingService;
use App\Domain\Trasporti\TrasportoTrackingPrepService;
use App\Domain\Trasporti\TrasportoTrackingService;
use App\Domain\Trasporti\TrasportoService;
use App\Domain\Fir\FirService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Domain\Rentri\RentriFirVidimaValidator;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\FirBlocco;
use App\Models\RentriSetting;
use App\Models\Trasporto;
use App\Services\Rentri\Contracts\RentriFirServiceInterface;
use App\Services\Rentri\Contracts\RentriFirSigningServiceInterface;
use App\Services\Rentri\Contracts\RentriXfirTransmissionServiceInterface;
use App\Services\Rentri\Exceptions\RentriXfirValidationException;
use App\Support\Rentri\FirActionRateLimiter;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Dettaglio trasporto')]
class TrasportoShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public Trasporto $trasporto;

    public function mount(Trasporto $trasporto): void
    {
        $this->authorize('view', $trasporto);
        $this->trasporto = $trasporto->load([
            'codiceCer',
            'destinatario',
            'svuotamento.trasportatore',
            'svuotamento.impianto',
            'firCollegato',
        ]);
    }

    public function avviaTransito(TrasportoService $trasporti): void
    {
        $this->authorize('update', $this->trasporto);

        try {
            $this->trasporto = $trasporti->avviaTransito($this->trasporto);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->trasporto->load(['codiceCer', 'destinatario', 'svuotamento.trasportatore']);
        session()->flash('success', 'Trasporto avviato — stato in transito.');
    }

    public function refreshGpsPosition(TrasportoGpsTrackingService $gpsTracking): void
    {
        $this->authorize('view', $this->trasporto);

        if (! $gpsTracking->isTrackingAvailable($this->trasporto)) {
            session()->flash('error', 'Tracking GPS disponibile solo per trasporti in transito.');

            return;
        }

        try {
            $this->trasporto = $gpsTracking->refreshPosition($this->trasporto->fresh());
        } catch (\App\Domain\Trasporti\TrasportoGpsTrackingException $e) {
            session()->flash('error', 'Aggiornamento GPS fallito: '.$e->getMessage());

            return;
        }

        session()->flash('success', 'Posizione GPS aggiornata.');
    }

    public function completa(TrasportoService $trasporti): void
    {
        $this->authorize('trasporto.complete', $this->trasporto);

        try {
            $this->trasporto = $trasporti->completa($this->trasporto);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->trasporto->load(['codiceCer', 'destinatario', 'svuotamento.trasportatore', 'firCollegato']);
        session()->flash('success', 'Trasporto completato: scarico magazzino e movimento registro registrati.');
    }

    public function annulla(TrasportoService $trasporti): void
    {
        $this->authorize('update', $this->trasporto);

        try {
            $this->trasporto = $trasporti->annulla($this->trasporto);
        } catch (\InvalidArgumentException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', 'Trasporto annullato; quantità svuotamento liberata.');
    }

    public function vidimaFir(RentriFirServiceInterface $rentriFir, FirActionRateLimiter $rateLimiter): void
    {
        $this->authorize('view', $this->trasporto);
        $this->authorize('fir.vidima');

        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        if ($rateLimiter->tooMany('vidima', $userId)) {
            session()->flash('error', $rateLimiter->message('vidima'));

            return;
        }

        try {
            $fir = $rentriFir->vidima($this->trasporto);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $rateLimiter->record('vidima', $userId);

        $this->trasporto->refresh()->load([
            'codiceCer',
            'destinatario',
            'svuotamento.trasportatore',
            'firCollegato',
        ]);

        session()->flash('success', $this->vidimaSuccessMessage($fir));
    }

    /**
     * @return non-empty-string
     */
    protected function vidimaSuccessMessage(\App\Models\Fir $fir): string
    {
        /** @var array<string, mixed> $qr */
        $qr = json_decode($fir->qr_payload ?? '{}', true) ?: [];
        $modeLabel = app(RentriRuntimeModeService::class)->apiModeDisplayLabelFromApiMode(
            (string) ($qr['api_mode'] ?? 'stub'),
        );
        $message = sprintf('FIR vidimato (%s): %s', $modeLabel, $fir->numero_fir);

        if (! empty($qr['protocollo'])) {
            $message .= ' — protocollo '.$qr['protocollo'];
        }

        return $message;
    }

    public function firmaXfir(RentriFirSigningServiceInterface $firSigning, FirActionRateLimiter $rateLimiter): void
    {
        $this->authorize('view', $this->trasporto);
        $this->authorize('fir.firma');

        $userId = auth()->id();
        abort_unless($userId !== null, 403);

        if ($rateLimiter->tooMany('firma', $userId)) {
            session()->flash('error', $rateLimiter->message('firma'));

            return;
        }

        $fir = $this->trasporto->firCollegato;

        if ($fir === null) {
            session()->flash('error', 'Vidima un FIR prima della firma xFIR.');

            return;
        }

        try {
            $fir = $firSigning->sign($fir);
        } catch (RentriXfirValidationException $e) {
            session()->flash('error', 'Validazione xFIR non superata — correggere i dati prima della firma.');
            session()->flash('xfir_validation_errors', $e->italianMessages);

            return;
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $rateLimiter->record('firma', $userId);

        $this->trasporto->refresh()->load([
            'codiceCer',
            'destinatario',
            'svuotamento.trasportatore',
            'firCollegato',
        ]);

        session()->flash('success', $this->firmaSuccessMessage($fir).' Puoi inviarlo a RENTRI con «Invia xFIR a MASE».');
    }

    public function inviaXfirMase(RentriXfirTransmissionServiceInterface $xfirTransmission): void
    {
        $this->authorize('view', $this->trasporto);
        $this->authorize('fir.firma');

        $fir = $this->trasporto->firCollegato;

        if ($fir === null) {
            session()->flash('error', 'Nessun FIR firmato da inviare.');

            return;
        }

        try {
            $fir = $xfirTransmission->transmit($fir);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->trasporto->refresh()->load([
            'codiceCer',
            'destinatario',
            'svuotamento.trasportatore',
            'firCollegato',
        ]);

        session()->flash('success', $this->xfirTransmissionSuccessMessage($fir));
    }

    /**
     * @return non-empty-string
     */
    protected function xfirTransmissionSuccessMessage(\App\Models\Fir $fir): string
    {
        $modeLabel = app(RentriRuntimeModeService::class)->apiModeDisplayLabel();
        $message = sprintf('xFIR inviato a MASE (%s): %s', $modeLabel, $fir->numero_fir);

        if (filled($fir->xfir_protocollo)) {
            $message .= ' — protocollo '.$fir->xfir_protocollo;
        }

        return $message;
    }

    public function downloadXfirFirmato(RentriFirSigningServiceInterface $firSigning): StreamedResponse
    {
        $this->authorize('view', $this->trasporto);
        $this->authorize('fir.firma');

        $fir = $this->trasporto->firCollegato;

        if ($fir === null || blank($fir->xfir_signed_payload)) {
            abort(404, 'Payload xFIR firmato non disponibile.');
        }

        $filename = $firSigning->signedPayloadFilename($fir);

        return response()->streamDownload(
            static fn () => print($fir->xfir_signed_payload),
            $filename,
            ['Content-Type' => 'application/json'],
        );
    }

    /**
     * @return non-empty-string
     */
    protected function firmaSuccessMessage(\App\Models\Fir $fir): string
    {
        /** @var array<string, mixed> $signed */
        $signed = json_decode($fir->xfir_signed_payload ?? '{}', true) ?: [];
        $modeLabel = app(RentriRuntimeModeService::class)->apiModeDisplayLabelFromApiMode(
            (string) ($signed['api_mode'] ?? 'stub'),
        );

        return sprintf('FIR firmato xFIR (%s): %s', $modeLabel, $fir->numero_fir);
    }

    public function render(
        TrasportoService $trasporti,
        TrasportoTrackingService $tracking,
        TrasportoTrackingPrepService $trackingPrep,
        TrasportoGpsTrackingService $gpsTracking,
        FirService $fir,
        FirBloccoService $blocchi,
        RentriFirSigningServiceInterface $firSigning,
        RentriXfirTransmissionServiceInterface $xfirTransmission,
        RentriFirVidimaValidator $vidimaValidator,
    ): View {
        $firCollegato = $this->trasporto->firCollegato;
        $trackingAvailable = $tracking->isTrackingAvailable($this->trasporto);
        $settings = RentriSetting::instance();
        $showVidimaSection = $firCollegato === null
            && in_array($this->trasporto->stato, [\App\Enums\TrasportoStato::InPreparazione, \App\Enums\TrasportoStato::InTransito], true);
        $vidimaChecklist = $showVidimaSection ? $vidimaValidator->checklist($settings) : [];
        $vidimaReady = $showVidimaSection && $vidimaValidator->isReady($settings);

        $runtimeMode = app(RentriRuntimeModeService::class);
        $gpsRuntime = app(\App\Domain\Trasporti\TrasportoGpsRuntimeModeService::class);
        $gpsPreflight = app(\App\Domain\Trasporti\TrasportoGpsPreflightService::class);
        $lastGpsPosition = $gpsTracking->lastPosition($this->trasporto);

        return $this->segreteriaView(
            'livewire.segreteria.trasporti.show',
            [
                'service'             => $trasporti,
                'firService'          => $fir,
                'completionBlockers'  => $trasporti->completionBlockers($this->trasporto),
                'canComplete'         => $trasporti->canComplete($this->trasporto),
                'canSignXfir'         => $firCollegato ? $firSigning->canSign($firCollegato) : false,
                'signBlockReason'     => $firCollegato ? $firSigning->signBlockReason($firCollegato) : null,
                'canTransmitXfir'     => $firCollegato
                    ? $xfirTransmission->canTransmit($firCollegato)
                    : false,
                'vidimaBlockers'      => $this->vidimaOperationalBlockers($blocchi),
                'vidimaChecklist'     => $vidimaChecklist,
                'vidimaReady'         => $vidimaReady,
                'canVidimaFir'        => $this->canVidimaFir($blocchi, $vidimaValidator),
                'rentriApiModeLabel'  => $runtimeMode->apiModeDisplayLabel($settings),
                'apiStub'             => $runtimeMode->isApiStub($settings),
                'trackingAvailable'   => $trackingAvailable,
                'trackingMapUrl'      => $tracking->mapSearchUrl($this->trasporto),
                'trackingPrepLabel'   => $trackingPrep->prepLabel(),
                'trackingTimeline'    => $trackingAvailable ? $trackingPrep->timeline($this->trasporto) : [],
                'trackingEtaStub'     => $trackingPrep->etaStub($this->trasporto),
                'gpsRuntime'          => $gpsRuntime,
                'gpsPreflight'        => $gpsPreflight->checklist(),
                'gpsPreflightReady'   => $gpsPreflight->isReady(),
                'lastGpsPosition'     => $lastGpsPosition,
                'gpsMapEmbedUrl'      => $gpsTracking->openStreetMapEmbedUrl($lastGpsPosition),
                'gpsMapLink'          => $gpsTracking->openStreetMapLink($lastGpsPosition),
            ],
            'trasporti',
            'Trasporto #'.$this->trasporto->id,
        );
    }

    /**
     * @return list<string>
     */
    /**
     * @return list<string>
     */
    private function vidimaOperationalBlockers(FirBloccoService $blocchi): array
    {
        if ($this->trasporto->fir_id !== null) {
            return [];
        }

        if (! in_array($this->trasporto->stato, [\App\Enums\TrasportoStato::InPreparazione, \App\Enums\TrasportoStato::InTransito], true)) {
            return ['Trasporto non in preparazione o in transito.'];
        }

        $blockers = [];
        $numIscrSito = RentriSetting::instance()->num_iscr_sito ?? '';
        $blocco = FirBlocco::query()
            ->when($numIscrSito !== '', fn ($q) => $q->where('num_iscr_sito', $numIscrSito))
            ->orderBy('id')
            ->first();

        if ($blocco === null) {
            $blockers[] = 'Nessun blocco FIR configurato — crea o sincronizza un blocco RENTRI.';
        } elseif ($blocchi->isEsaurito($blocco)) {
            $blockers[] = sprintf(
                'Blocco «%s» esaurito (%d/%d progressivi). Crea o sincronizza un nuovo blocco.',
                $blocco->codice_blocco,
                $blocco->progressivo_ultimo,
                FirBlocco::progressivoMax(),
            );
        }

        return $blockers;
    }

    private function canVidimaFir(FirBloccoService $blocchi, RentriFirVidimaValidator $vidimaValidator): bool
    {
        return $vidimaValidator->isReady(RentriSetting::instance())
            && $this->vidimaOperationalBlockers($blocchi) === [];
    }
}
