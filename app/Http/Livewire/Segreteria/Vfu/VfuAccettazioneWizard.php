<?php

namespace App\Http\Livewire\Segreteria\Vfu;

use App\Domain\Vfu\VfuAccettazioneService;
use App\Domain\Vfu\VfuDocumentService;
use App\Enums\VfuTipoDocumento;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\VfuRegistration;
use App\Support\UploadValidation;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Accettazione VFU')]
class VfuAccettazioneWizard extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public int $step = 1;

    public const TOTAL_STEPS = 4;

    public ?VfuRegistration $vfuRegistration = null;

    public string $tipo_veicolo = 'Autovettura';

    public string $nazione = 'Italia';

    public string $targa = '';

    public string $telaio = '';

    public string $codice_motore = '';

    public string $marca = '';

    public string $modello = '';

    public string $peso_kg = '';

    public string $nome = '';

    public string $cognome = '';

    public string $proprietario = '';

    public string $codice_fiscale = '';

    public string $indirizzo = '';

    public string $comune = '';

    public string $provincia = '';

    public string $luogo_nascita = '';

    public $certificatoPdf;

    public $documentoIdentita;

    public $cartaCircolazione;

    public $denunciaSmarrimento;

    public $certificatoProprieta;

    public $delega;

    /** @var array<string, string> */
    public array $extractedPreview = [];

    public function mount(?VfuRegistration $vfuRegistration = null): void
    {
        if ($vfuRegistration?->exists) {
            $this->authorize('update', $vfuRegistration);
            $vfuRegistration->load('documents');
            $this->vfuRegistration = $vfuRegistration;
            $this->fillFromModel($vfuRegistration);
        } else {
            $this->authorize('create', VfuRegistration::class);
        }
    }

    public function nextStep(): void
    {
        if ($this->step === 1) {
            $this->validate([
                'tipo_veicolo' => ['required', 'string', 'max:50'],
                'nazione' => ['required', 'string', 'max:50'],
                'targa' => ['required', 'string', 'max:20'],
                'telaio' => ['required', 'string', 'max:50'],
                'codice_motore' => ['required', 'string', 'max:80'],
                'peso_kg' => ['required', 'numeric', 'min:1'],
            ]);
            $this->persistDraft();
        }

        if ($this->step < self::TOTAL_STEPS) {
            $this->step++;
        }
    }

    public function previousStep(): void
    {
        if ($this->step > 1) {
            $this->step--;
        }
    }

    public function goToStep(int $step): void
    {
        if ($step >= 1 && $step <= self::TOTAL_STEPS && $step <= $this->step) {
            $this->step = $step;
        }
    }

    public function persistDraft(VfuAccettazioneService $service = null): void
    {
        $service ??= app(VfuAccettazioneService::class);
        $this->vfuRegistration = $service->saveDraft($this->vfuRegistration, $this->vehiclePayload());
    }

    public function uploadCertificato(VfuDocumentService $documents, VfuAccettazioneService $service): void
    {
        $this->validate(['certificatoPdf' => UploadValidation::pdfRules()]);
        $this->persistDraft($service);

        $result = $documents->storeCertificatoProvvisorio($this->vfuRegistration, $this->certificatoPdf);
        $this->extractedPreview = $result['extracted'];
        $this->applyExtracted($result['extracted']);
        $this->certificatoPdf = null;
        $this->vfuRegistration->refresh()->load('documents');
        session()->flash('success', 'Certificato caricato; dati estratti dal PDF.');
    }

    public function uploadDocument(string $tipo, VfuDocumentService $documents, VfuAccettazioneService $service): void
    {
        $enum = VfuTipoDocumento::from($tipo);
        $property = match ($enum) {
            VfuTipoDocumento::DocumentoIdentita => 'documentoIdentita',
            VfuTipoDocumento::CartaCircolazione => 'cartaCircolazione',
            VfuTipoDocumento::DenunciaSmarrimento => 'denunciaSmarrimento',
            VfuTipoDocumento::CertificatoProprieta => 'certificatoProprieta',
            VfuTipoDocumento::Delega => 'delega',
            default => null,
        };

        if (! $property) {
            return;
        }

        $this->validate([
            $property => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
        ]);

        $this->persistDraft($service);
        $documents->store($this->vfuRegistration, $this->{$property}, $enum);
        $this->{$property} = null;
        $this->vfuRegistration->refresh()->load('documents');
        session()->flash('success', $enum->label().' caricato.');
    }

    public function confirm(VfuAccettazioneService $service): void
    {
        $this->authorize($this->vfuRegistration ? 'update' : 'create', $this->vfuRegistration ?? VfuRegistration::class);
        $this->persistDraft($service);

        try {
            $this->vfuRegistration = $service->completeAccettazione($this->vfuRegistration);
        } catch (ValidationException $e) {
            $this->addError('confirm', collect($e->errors())->flatten()->first());
            $this->step = 2;

            return;
        }

        session()->flash('success', 'Accettazione completata. Veicolo in attesa bonifica.');
        $this->redirect(route('segreteria.vfu.show', $this->vfuRegistration), navigate: true);
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.vfu.wizard',
            [
                'documentTypes' => [
                    VfuTipoDocumento::DocumentoIdentita,
                    VfuTipoDocumento::CartaCircolazione,
                    VfuTipoDocumento::DenunciaSmarrimento,
                    VfuTipoDocumento::CertificatoProprieta,
                    VfuTipoDocumento::Delega,
                ],
            ],
            'vfu',
            $this->vfuRegistration ? 'Modifica accettazione' : 'Nuova accettazione',
        );
    }

    private function vehiclePayload(): array
    {
        return [
            'tipo_veicolo' => $this->tipo_veicolo,
            'nazione' => $this->nazione,
            'targa' => $this->targa,
            'telaio' => $this->telaio,
            'codice_motore' => $this->codice_motore,
            'marca' => $this->marca,
            'modello' => $this->modello,
            'peso_kg' => $this->peso_kg,
            'nome' => $this->nome,
            'cognome' => $this->cognome,
            'proprietario' => $this->proprietario,
            'codice_fiscale' => $this->codice_fiscale,
            'indirizzo' => $this->indirizzo,
            'comune' => $this->comune,
            'provincia' => $this->provincia,
            'luogo_nascita' => $this->luogo_nascita,
        ];
    }

    private function fillFromModel(VfuRegistration $vfu): void
    {
        $this->tipo_veicolo = $vfu->tipo_veicolo ?? 'Autovettura';
        $this->nazione = $vfu->nazione ?? 'Italia';
        $this->targa = $vfu->targa;
        $this->telaio = $vfu->telaio;
        $this->codice_motore = $vfu->codice_motore ?? '';
        $this->marca = $vfu->marca ?? '';
        $this->modello = $vfu->modello ?? '';
        $this->peso_kg = (string) $vfu->peso_kg;
        $this->nome = $vfu->nome ?? '';
        $this->cognome = $vfu->cognome ?? '';
        $this->proprietario = $vfu->proprietario ?? '';
        $this->codice_fiscale = $vfu->codice_fiscale ?? '';
        $this->indirizzo = $vfu->indirizzo ?? '';
        $this->comune = $vfu->comune ?? '';
        $this->provincia = $vfu->provincia ?? '';
        $this->luogo_nascita = $vfu->luogo_nascita ?? '';
    }

    /** @param array<string, string> $data */
    private function applyExtracted(array $data): void
    {
        foreach (['targa', 'telaio', 'marca', 'modello', 'nome', 'cognome', 'codice_fiscale', 'proprietario', 'indirizzo', 'comune', 'provincia', 'luogo_nascita'] as $key) {
            if (! empty($data[$key])) {
                $this->{$key} = $data[$key];
            }
        }
        if (! empty($data['nome']) || ! empty($data['cognome'])) {
            $this->proprietario = trim(($data['nome'] ?? '').' '.($data['cognome'] ?? ''));
        }
    }
}
