<?php

namespace App\Http\Livewire\Segreteria\Vfu;

use App\Domain\Notifications\MailTransportRuntimeService;
use App\Domain\Vfu\CertificatoRottamazioneGeneratorService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuDocumentoService;
use App\Domain\Vfu\VfuDocumentService;
use App\Domain\Vfu\VfuNotificationService;
use App\Domain\Vfu\VfuStoricoExportService;
use App\Domain\Vfu\VfuTimelineService;
use App\Enums\VfuAllegatoTipo;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\User;
use App\Models\VfuDocument;
use App\Models\VfuDocumento;
use App\Models\VfuRegistration;
use App\Support\UploadValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Dettaglio VFU')]
class VfuShow extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public VfuRegistration $vfuRegistration;

    public ?int $agenziaId = null;

    public ?int $operatoreAssegnatoId = null;

    public bool $showCertificatoPreview = false;

    public $allegatoUpload = null;

    public string $allegatoTipo = 'altro';

    public function mount(VfuRegistration $vfuRegistration): void
    {
        $this->authorize('view', $vfuRegistration);
        $this->vfuRegistration = $vfuRegistration->load([
            'documents',
            'documenti.uploader',
            'agenzia',
            'operatoreAssegnato',
            'registroMovimenti.codiceCer',
            'smontaggioAttivo.ricambi',
        ]);
        $this->agenziaId = $vfuRegistration->agenzia_anagrafica_id;
        $this->operatoreAssegnatoId = $vfuRegistration->operatore_assegnato_id;
    }

    public function downloadDocument(int $documentId): StreamedResponse
    {
        $doc = VfuDocument::where('vfu_registration_id', $this->vfuRegistration->id)->findOrFail($documentId);
        $this->authorize('view', $this->vfuRegistration);

        abort_unless(Storage::disk('public')->exists($doc->path), 404);

        return Storage::disk('public')->download($doc->path, $doc->original_name);
    }

    public function uploadAllegato(VfuDocumentoService $service): void
    {
        $this->authorize('create', [VfuDocumento::class, $this->vfuRegistration]);

        $validated = $this->validate([
            'allegatoUpload' => UploadValidation::vfuAllegatoRules(),
            'allegatoTipo'   => ['required', 'in:'.implode(',', array_column(VfuAllegatoTipo::cases(), 'value'))],
        ]);

        $tipo = VfuAllegatoTipo::from($validated['allegatoTipo']);
        $service->upload($this->vfuRegistration, $validated['allegatoUpload'], $tipo, auth()->user());

        $this->reset('allegatoUpload');
        $this->vfuRegistration->load('documenti.uploader');
        session()->flash('success', 'Allegato caricato.');
    }

    public function downloadAllegato(int $documentoId, VfuDocumentoService $service): StreamedResponse
    {
        $documento = VfuDocumento::where('vfu_registration_id', $this->vfuRegistration->id)->findOrFail($documentoId);
        $this->authorize('view', $documento);

        return $service->download($documento);
    }

    public function deleteAllegato(int $documentoId, VfuDocumentoService $service): void
    {
        $documento = VfuDocumento::where('vfu_registration_id', $this->vfuRegistration->id)->findOrFail($documentoId);
        $this->authorize('delete', $documento);

        $service->delete($documento);
        $this->vfuRegistration->load('documenti.uploader');
        session()->flash('success', 'Allegato eliminato.');
    }

    public function exportStoricoCsv(VfuStoricoExportService $export): StreamedResponse
    {
        $this->authorize('exportStorico', $this->vfuRegistration);

        return $export->exportCsvFor($this->vfuRegistration);
    }

    public function downloadCertificato(CertificatoRottamazioneGeneratorService $generator): StreamedResponse
    {
        $this->authorize('downloadCertificato', $this->vfuRegistration);

        return $generator->downloadResponse($this->vfuRegistration);
    }

    public function toggleCertificatoPreview(CertificatoRottamazioneGeneratorService $generator): void
    {
        $this->authorize('downloadCertificato', $this->vfuRegistration);

        if (! $this->showCertificatoPreview) {
            $generator->assertEligible($this->vfuRegistration);
        }

        $this->showCertificatoPreview = ! $this->showCertificatoPreview;
    }

    public function inviaAgenzia(
        VfuAccettazioneService $service,
        VfuNotificationService $notifications,
        MailTransportRuntimeService $mailRuntime,
    ): void {
        $this->authorize('update', $this->vfuRegistration);

        $this->validate([
            'agenziaId' => ['required', 'integer', 'exists:anagrafiche,id'],
        ]);

        $this->vfuRegistration = $service->inviaAgenzia($this->vfuRegistration, (int) $this->agenziaId);

        $agenzia = $this->vfuRegistration->agenzia;

        if ($agenzia) {
            $notifications->notifyConsegnaAgenzia($this->vfuRegistration, $agenzia);
        }

        if ($mailRuntime->isLive()) {
            $message = 'Pratica inviata ad agenzia'.($agenzia ? ' ('.$agenzia->ragione_sociale.')' : '').'. Email di notifica inviata.';
        } else {
            $message = 'Pratica segnata come inviata ad agenzia'.($agenzia ? ' ('.$agenzia->ragione_sociale.')' : '').'. Notifica simulata (modalità stub — NOTIFICATIONS_LIVE=false).';
        }

        session()->flash('success', $message);
    }

    public function assegnaOperatore(VfuAccettazioneService $service): void
    {
        $this->authorize('update', $this->vfuRegistration);

        $this->validate([
            'operatoreAssegnatoId' => ['required', 'integer', 'exists:users,id'],
        ]);

        $operatore = User::role('operatore')->findOrFail((int) $this->operatoreAssegnatoId);

        $this->vfuRegistration = $service->assegnaOperatore($this->vfuRegistration, $operatore);

        session()->flash('success', "VFU assegnato a {$operatore->name}.");
    }

    public function rottama(VfuAccettazioneService $service): void
    {
        $this->authorize('rottama', $this->vfuRegistration);

        $this->vfuRegistration = $service->rottama($this->vfuRegistration);

        session()->flash('success', 'Pratica chiusa — veicolo segnato come rottamato.');
    }

    public function delete(VfuAccettazioneService $service, VfuDocumentService $documents): void
    {
        $this->authorize('delete', $this->vfuRegistration);
        $service->delete($this->vfuRegistration);
        session()->flash('success', 'Registrazione VFU eliminata.');
        $this->redirect(route('segreteria.vfu.index'), navigate: true);
    }

    private function canCreateFattura(): bool
    {
        $user = auth()->user();

        if ($user === null || ! $user->hasRole(['admin', 'segreteria'])) {
            return false;
        }

        return in_array($this->vfuRegistration->stato, [
            \App\Enums\VfuStato::Bonificato,
            \App\Enums\VfuStato::InSmontaggio,
            \App\Enums\VfuStato::Smontato,
            \App\Enums\VfuStato::Rottamato,
        ], true);
    }

    public function render(VfuTimelineService $timeline): View
    {
        $generator = app(CertificatoRottamazioneGeneratorService::class);
        $agenzie = \App\Models\Anagrafica::query()
            ->where('tipo', 'agenzia_pratiche')
            ->orderBy('ragione_sociale')
            ->get();
        $operatori = User::role('operatore')->orderBy('name')->get();

        return $this->segreteriaView(
            'livewire.segreteria.vfu.show',
            [
                'agenzie'                => $agenzie,
                'operatori'              => $operatori,
                'timelineSteps'          => $timeline->steps($this->vfuRegistration),
                'certificatoEligible'    => $generator->isEligible($this->vfuRegistration),
                'certificatoPreviewHtml' => $this->showCertificatoPreview
                    ? $generator->renderHtml($this->vfuRegistration)
                    : null,
                'allegatoTipi'           => VfuAllegatoTipo::cases(),
                'canCreateFattura'       => $this->canCreateFattura(),
                'smontaggioSession'      => $this->vfuRegistration->smontaggioAttivo,
            ],
            'vfu',
            $this->vfuRegistration->targa,
        );
    }
}
