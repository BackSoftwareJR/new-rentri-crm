<?php

namespace App\Http\Livewire\Segreteria\Vfu;

use App\Domain\Vfu\CertificatoRottamazioneGeneratorService;
use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuDocumentoService;
use App\Domain\Vfu\VfuDocumentService;
use App\Domain\Vfu\VfuStoricoExportService;
use App\Domain\Vfu\VfuTimelineService;
use App\Enums\VfuAllegatoTipo;
use App\Http\Livewire\Segreteria\SegreteriaPage;
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

    public bool $showCertificatoPreview = false;

    public $allegatoUpload = null;

    public string $allegatoTipo = 'altro';

    public function mount(VfuRegistration $vfuRegistration): void
    {
        $this->authorize('view', $vfuRegistration);
        $this->vfuRegistration = $vfuRegistration->load(['documents', 'documenti.uploader', 'agenzia', 'registroMovimenti.codiceCer']);
        $this->agenziaId = $vfuRegistration->agenzia_anagrafica_id;
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

    public function inviaAgenzia(VfuAccettazioneService $service): void
    {
        $this->authorize('update', $this->vfuRegistration);

        $this->validate([
            'agenziaId' => ['required', 'integer', 'exists:anagrafiche,id'],
        ]);

        $this->vfuRegistration = $service->inviaAgenziaStub($this->vfuRegistration, (int) $this->agenziaId);
        session()->flash('success', 'Pratica segnata come inviata ad agenzia (stub — email non ancora collegata).');
    }

    public function delete(VfuAccettazioneService $service, VfuDocumentService $documents): void
    {
        $this->authorize('delete', $this->vfuRegistration);
        $service->delete($this->vfuRegistration);
        session()->flash('success', 'Registrazione VFU eliminata.');
        $this->redirect(route('segreteria.vfu.index'), navigate: true);
    }

    public function render(VfuTimelineService $timeline): View
    {
        $generator = app(CertificatoRottamazioneGeneratorService::class);
        $agenzie = \App\Models\Anagrafica::query()
            ->where('tipo', 'agenzia_pratiche')
            ->orderBy('ragione_sociale')
            ->get();

        return $this->segreteriaView(
            'livewire.segreteria.vfu.show',
            [
                'agenzie'                => $agenzie,
                'timelineSteps'          => $timeline->steps($this->vfuRegistration),
                'certificatoEligible'    => $generator->isEligible($this->vfuRegistration),
                'certificatoPreviewHtml' => $this->showCertificatoPreview
                    ? $generator->renderHtml($this->vfuRegistration)
                    : null,
                'allegatoTipi'           => VfuAllegatoTipo::cases(),
            ],
            'vfu',
            $this->vfuRegistration->targa,
        );
    }
}
