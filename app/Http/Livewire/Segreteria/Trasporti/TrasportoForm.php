<?php

namespace App\Http\Livewire\Segreteria\Trasporti;

use App\Domain\Fir\FirBloccoService;
use App\Domain\Trasporti\TrasportoService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Anagrafica;
use App\Models\CodiceCer;
use App\Models\FirBlocco;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

#[Title('Nuovo trasporto')]
class TrasportoForm extends SegreteriaPage
{
    use AuthorizesRequests;

    public string $trasportatoreId = '';

    public string $destinatarioId = '';

    public string $trasportatoreSearch = '';

    public string $destinatarioSearch = '';

    public string $targaMezzo = '';

    public string $conducente = '';

    public string $dataTrasporto = '';

    public string $codiceCerId = '';

    public string $quantitaKg = '';

    public string $vfuRegistrationId = '';

    public string $vfuSearch = '';

    public string $firBloccoId = '';

    public string $note = '';

    public function mount(): void
    {
        $this->authorize('create', Trasporto::class);
        $this->dataTrasporto = now()->toDateString();
    }

    public function save(TrasportoService $trasporti): void
    {
        $this->authorize('create', Trasporto::class);

        $this->validate([
            'trasportatoreId'   => ['nullable', 'exists:anagrafiche,id'],
            'destinatarioId'    => ['required', 'exists:anagrafiche,id'],
            'targaMezzo'        => ['nullable', 'string', 'max:20'],
            'conducente'        => ['nullable', 'string', 'max:120'],
            'dataTrasporto'     => ['required', 'date'],
            'codiceCerId'       => ['required', 'exists:codici_cer,id'],
            'quantitaKg'        => ['required', 'numeric', 'min:0.0001'],
            'vfuRegistrationId' => ['nullable', 'exists:vfu_registrations,id'],
            'firBloccoId'       => ['nullable', 'exists:fir_blocchi,id'],
            'note'              => ['nullable', 'string', 'max:2000'],
        ]);

        try {
            $trasporto = $trasporti->crea([
                'anagrafica_trasportatore_id' => $this->trasportatoreId !== '' ? (int) $this->trasportatoreId : null,
                'anagrafica_destinatario_id'  => (int) $this->destinatarioId,
                'codice_cer_id'               => (int) $this->codiceCerId,
                'quantita_kg'                 => $this->quantitaKg,
                'targa_mezzo'                 => $this->targaMezzo,
                'conducente'                  => $this->conducente,
                'data_trasporto'              => $this->dataTrasporto,
                'vfu_registration_id'         => $this->vfuRegistrationId !== '' ? (int) $this->vfuRegistrationId : null,
                'fir_blocco_id'               => $this->firBloccoId !== '' ? (int) $this->firBloccoId : null,
                'note'                        => $this->note,
            ]);
        } catch (\InvalidArgumentException $e) {
            $this->addError('quantitaKg', $e->getMessage());

            return;
        }

        session()->flash('success', 'Trasporto creato in preparazione.');
        $this->redirect(route('segreteria.trasporti.show', $trasporto), navigate: true);
    }

    #[Computed]
    public function trasportatori()
    {
        $query = Anagrafica::query()
            ->where(function ($q) {
                $q->where('tipo', 'trasportatore')
                    ->orWhere(function ($q2) {
                        $q2->where('tipo', 'impianto')->where('gestisce_trasporti', true);
                    });
            })
            ->orderBy('ragione_sociale');

        if ($this->trasportatoreSearch !== '') {
            $term = '%'.$this->trasportatoreSearch.'%';
            $query->where(function ($q) use ($term) {
                $q->where('ragione_sociale', 'like', $term)
                    ->orWhere('piva', 'like', $term);
            });
        }

        $results = $query->limit(20)->get(['id', 'ragione_sociale', 'piva']);

        if ($this->trasportatoreId !== '' && ! $results->contains('id', (int) $this->trasportatoreId)) {
            $selected = Anagrafica::query()
                ->whereKey((int) $this->trasportatoreId)
                ->first(['id', 'ragione_sociale', 'piva']);

            if ($selected) {
                $results->prepend($selected);
            }
        }

        return $results;
    }

    #[Computed]
    public function destinatari()
    {
        $query = Anagrafica::query()
            ->where('tipo', 'impianto')
            ->orderBy('ragione_sociale');

        if ($this->destinatarioSearch !== '') {
            $term = '%'.$this->destinatarioSearch.'%';
            $query->where(function ($q) use ($term) {
                $q->where('ragione_sociale', 'like', $term)
                    ->orWhere('piva', 'like', $term);
            });
        }

        $results = $query->limit(20)->get(['id', 'ragione_sociale', 'piva']);

        if ($this->destinatarioId !== '' && ! $results->contains('id', (int) $this->destinatarioId)) {
            $selected = Anagrafica::query()
                ->whereKey((int) $this->destinatarioId)
                ->first(['id', 'ragione_sociale', 'piva']);

            if ($selected) {
                $results->prepend($selected);
            }
        }

        return $results;
    }

    #[Computed]
    public function vfuOptions()
    {
        $query = VfuRegistration::query()->forActiveSito()->orderByDesc('created_at');

        if ($this->vfuSearch !== '') {
            $term = '%'.$this->vfuSearch.'%';
            $query->where(function ($q) use ($term) {
                $q->where('targa', 'like', $term)
                    ->orWhere('telaio', 'like', $term);
            });
        }

        return $query->limit(20)->get(['id', 'targa', 'telaio', 'marca', 'modello']);
    }

    #[Computed]
    public function codiciCer()
    {
        return CodiceCer::query()
            ->where('attivo', true)
            ->orderBy('codice')
            ->get(['id', 'codice', 'descrizione', 'um']);
    }

    #[Computed]
    public function blocchiFir(FirBloccoService $blocchi)
    {
        return $blocchi->list()->filter(fn (FirBlocco $b) => ! $blocchi->isEsaurito($b))->values();
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.trasporti.trasporto-form',
            [],
            'trasporti',
            'Nuovo trasporto',
        );
    }
}
