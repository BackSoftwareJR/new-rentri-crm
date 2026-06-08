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

    public string $email_proprietario = '';

    public string $pec_proprietario = '';

    public string $data_nascita = '';

    public string $nazionalita_proprietario = 'Italiana';

    public string $provincia_nascita = '';

    public string $tipo_documento_identita = '';

    public string $numero_documento_identita = '';

    public string $note_carrozzeria = '';

    public string $provenienza_veicolo = '';

    public bool $targa_estera = false;

    public string $targa_estera_valore = '';

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
                'email_proprietario' => ['nullable', 'email', 'max:255'],
                'pec_proprietario' => ['nullable', 'email', 'max:255'],
                'data_nascita' => ['nullable', 'date'],
                'luogo_nascita' => ['nullable', 'string', 'max:100'],
                'provincia_nascita' => ['nullable', 'string', 'size:2'],
                'nazionalita_proprietario' => ['required', 'string', 'max:80'],
                'tipo_documento_identita' => ['nullable', 'in:CI,passaporto,patente'],
                'numero_documento_identita' => ['nullable', 'string', 'max:50'],
                'note_carrozzeria' => ['nullable', 'string', 'max:2000'],
                'provenienza_veicolo' => ['nullable', 'in:privato,assicurazione,officina,altro'],
                'targa_estera' => ['boolean'],
                'targa_estera_valore' => ['required_if:targa_estera,true', 'nullable', 'string', 'max:20'],
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

    public function fillFromScan(string $value, ?string $target = null): void
    {
        $value = strtoupper(trim($value));

        if ($value === '') {
            return;
        }

        if ($target === 'targa') {
            $this->targa = preg_replace('/\s+/', '', $value) ?? $value;

            return;
        }

        if ($target === 'telaio') {
            $this->telaio = preg_replace('/\s+/', '', $value) ?? $value;

            return;
        }

        if (strlen($value) === 17 && preg_match('/^[A-HJ-NPR-Z0-9]{17}$/', $value)) {
            $this->telaio = $value;
        } else {
            $this->targa = preg_replace('/\s+/', '', $value) ?? $value;
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
            $property => UploadValidation::vfuAllegatoRules(),
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
            'email_proprietario' => $this->email_proprietario,
            'pec_proprietario' => $this->pec_proprietario,
            'data_nascita' => $this->data_nascita ?: null,
            'nazionalita_proprietario' => $this->nazionalita_proprietario,
            'provincia_nascita' => $this->provincia_nascita,
            'tipo_documento_identita' => $this->tipo_documento_identita ?: null,
            'numero_documento_identita' => $this->numero_documento_identita ?: null,
            'note_carrozzeria' => $this->note_carrozzeria ?: null,
            'provenienza_veicolo' => $this->provenienza_veicolo ?: null,
            'targa_estera' => $this->targa_estera,
            'targa_estera_valore' => $this->targa_estera ? $this->targa_estera_valore : null,
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
        $this->email_proprietario = $vfu->email_proprietario ?? '';
        $this->pec_proprietario = $vfu->pec_proprietario ?? '';
        $this->data_nascita = $vfu->data_nascita?->toDateString() ?? '';
        $this->nazionalita_proprietario = $vfu->nazionalita_proprietario ?? 'Italiana';
        $this->provincia_nascita = $vfu->provincia_nascita ?? '';
        $this->tipo_documento_identita = $vfu->tipo_documento_identita ?? '';
        $this->numero_documento_identita = $vfu->numero_documento_identita ?? '';
        $this->note_carrozzeria = $vfu->note_carrozzeria ?? '';
        $this->provenienza_veicolo = $vfu->provenienza_veicolo ?? '';
        $this->targa_estera = (bool) $vfu->targa_estera;
        $this->targa_estera_valore = $vfu->targa_estera_valore ?? '';
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
