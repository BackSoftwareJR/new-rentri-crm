<?php

namespace App\Http\Livewire\Segreteria\Magazzino;

use App\Domain\Magazzino\MagazzinoService;
use App\Domain\Magazzino\MagazzinoSvuotamentoService;
use App\Domain\Magazzino\SerbatoioAlertService;
use App\Domain\Registro\RegistroService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Dettaglio serbatoio')]
class SerbatoioShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public CodiceCer $codiceCer;

    public string $peso_kg = '';

    public string $note = '';

    public ?int $impianto_id = null;

    public ?int $trasportatore_id = null;

    public bool $trasportatore_omesso = false;

    public string $svuotamento_quantita_kg = '';

    public string $svuotamento_note = '';

    public string $soglia_minima_kg = '';

    public function mount(CodiceCer $codiceCer, MagazzinoSvuotamentoService $svuotamenti): void
    {
        $this->authorize('magazzino.view', $codiceCer);
        $this->codiceCer = $codiceCer->load('magazzino');
        $this->soglia_minima_kg = $codiceCer->magazzino?->soglia_minima_kg !== null
            ? (string) $codiceCer->magazzino->soglia_minima_kg
            : '';

        if ($svuotamenti->puoRichiedereSvuotamento($this->codiceCer)) {
            $this->svuotamento_quantita_kg = (string) $svuotamenti->quantitaDisponibile($this->codiceCer->id);
        }
    }

    public function salvaCarico(MagazzinoService $magazzino): void
    {
        $this->authorize('magazzino.caricoManuale', $this->codiceCer);

        $validated = $this->validate([
            'peso_kg' => ['required', 'numeric', 'min:0.0001', 'max:9999999'],
            'note'    => ['required', 'string', 'min:3', 'max:5000'],
        ]);

        $magazzino->caricoManuale(
            $this->codiceCer->id,
            (float) $validated['peso_kg'],
            $validated['note'],
            (int) auth()->id(),
        );

        $this->codiceCer->refresh()->load('magazzino');
        $this->reset(['peso_kg', 'note']);
        $this->resetValidation();

        session()->flash('success', 'Carico manuale registrato in magazzino e registro movimenti.');
    }

    public function richiediSvuotamento(MagazzinoSvuotamentoService $svuotamenti): void
    {
        $this->authorize('magazzino.richiediSvuotamento', $this->codiceCer);

        $validated = $this->validate([
            'impianto_id'              => ['required', 'integer', 'exists:anagrafiche,id'],
            'trasportatore_id'         => ['nullable', 'integer', 'exists:anagrafiche,id'],
            'trasportatore_omesso'     => ['boolean'],
            'svuotamento_quantita_kg'  => ['required', 'numeric', 'min:0.0001', 'max:9999999'],
            'svuotamento_note'         => ['nullable', 'string', 'max:5000'],
        ], [
            'impianto_id.required' => 'Selezionare un impianto di destinazione.',
        ]);

        if (! $validated['trasportatore_omesso'] && empty($validated['trasportatore_id'])) {
            $this->addError('trasportatore_id', 'Selezionare un trasportatore conforme oppure spuntare "Trasportatore non indicato".');

            return;
        }

        try {
            $svuotamenti->richiediSvuotamento(
                $this->codiceCer->id,
                (int) $validated['impianto_id'],
                isset($validated['trasportatore_id']) ? (int) $validated['trasportatore_id'] : null,
                (bool) $validated['trasportatore_omesso'],
                (float) $validated['svuotamento_quantita_kg'],
                $validated['svuotamento_note'] ?? null,
                (int) auth()->id(),
            );
        } catch (\InvalidArgumentException $e) {
            $this->addError('svuotamento_quantita_kg', $e->getMessage());

            return;
        }

        $this->codiceCer->refresh()->load('magazzino');
        $this->reset(['impianto_id', 'trasportatore_id', 'trasportatore_omesso', 'svuotamento_note']);
        $this->trasportatore_omesso = false;
        $this->svuotamento_quantita_kg = (string) $svuotamenti->quantitaDisponibile($this->codiceCer->id);
        $this->resetValidation();

        session()->flash('success', 'Richiesta di svuotamento registrata. La giacenza resta impegnata fino al trasporto.');
    }

    public function salvaSogliaMinima(MagazzinoService $magazzino): void
    {
        $this->authorize('magazzino.caricoManuale', $this->codiceCer);

        $validated = $this->validate([
            'soglia_minima_kg' => ['nullable', 'numeric', 'min:0', 'max:9999999'],
        ]);

        $value = filled($validated['soglia_minima_kg'] ?? null)
            ? (float) $validated['soglia_minima_kg']
            : null;

        $magazzino->updateSogliaMinima($this->codiceCer->id, $value);
        $this->codiceCer->refresh()->load('magazzino');
        $this->soglia_minima_kg = $value !== null ? (string) $value : '';
        $this->resetValidation();

        session()->flash('success', 'Soglia minima giacenza aggiornata.');
    }

    public function render(
        MagazzinoService $magazzino,
        RegistroService $registro,
        MagazzinoSvuotamentoService $svuotamenti,
        SerbatoioAlertService $serbatoioAlerts,
    ): View {
        $detail = $magazzino->getSerbatoioDetail($this->codiceCer->id);
        $cronologia = $registro->cronologiaPerCer($this->codiceCer->id);

        return $this->segreteriaView(
            'livewire.segreteria.magazzino.show',
            [
                'serbatoio'            => $detail,
                'serbatoioAlert'       => $serbatoioAlerts->alertForCer($this->codiceCer->id),
                'cronologia'           => $cronologia,
                'magazzino'            => $magazzino,
                'puoSvuotare'          => $svuotamenti->puoRichiedereSvuotamento($this->codiceCer),
                'quantitaDisponibile'  => $svuotamenti->quantitaDisponibile($this->codiceCer->id),
                'quantitaImpegnata'    => $svuotamenti->quantitaImpegnata($this->codiceCer->id),
                'impianti'             => $svuotamenti->listImpiantiDestinazione(),
                'trasportatori'        => $svuotamenti->listTrasportatoriConformi(),
                'storicoSvuotamenti'   => $svuotamenti->storicoPerCer($this->codiceCer->id),
            ],
            'magazzino',
            $this->codiceCer->codice,
        );
    }
}
