<?php

namespace App\Http\Livewire\Segreteria\Fatturazione;

use App\Domain\Fatturazione\FatturazioneService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Anagrafica;
use App\Models\EcommerceOrdine;
use App\Models\Fattura;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;

#[Title('Nuova Fattura')]
class FatturaForm extends SegreteriaPage
{
    use AuthorizesRequests;

    public ?int $fatturaId = null;

    // Header
    public string $tipo = 'fattura';

    public string $anagraficaId = '';

    public string $dataEmissione = '';

    public string $dataScadenza = '';

    public int $ivaPercentuale = 22;

    public string $metodoPagamento = '';

    public string $note = '';

    public string $riferimentoVfuId = '';

    public ?int $ecommerceOrdineId = null;

    public string $anagraficaSearch = '';

    public string $vfuSearch = '';

    /** @var array<int, array{descrizione: string, quantita: string, prezzo_unitario: string, iva_percentuale: string}> */
    public array $righe = [];

    // Computed totals (updated reactively)
    public float $imponibile = 0;

    public float $ivaImporto = 0;

    public float $totale = 0;

    public function mount(?Fattura $fattura = null): void
    {
        if ($fattura?->exists) {
            $this->authorize('update', $fattura);
            $this->fatturaId = $fattura->id;
            $this->tipo = $fattura->tipo;
            $this->anagraficaId = (string) $fattura->anagrafica_id;
            $this->dataEmissione = $fattura->data_emissione?->toDateString() ?? '';
            $this->dataScadenza = $fattura->data_scadenza?->toDateString() ?? '';
            $this->ivaPercentuale = $fattura->iva_percentuale;
            $this->metodoPagamento = $fattura->metodo_pagamento ?? '';
            $this->note = $fattura->note ?? '';
            $this->riferimentoVfuId = (string) ($fattura->riferimento_vfu_id ?? '');
            $this->righe = $fattura->righe->map(fn ($r) => [
                'descrizione' => $r->descrizione,
                'quantita' => (string) $r->quantita,
                'prezzo_unitario' => (string) $r->prezzo_unitario,
                'iva_percentuale' => (string) $r->iva_percentuale,
            ])->toArray();
        } else {
            $this->authorize('create', Fattura::class);
            $this->dataEmissione = now()->toDateString();

            $vfuId = request()->query('riferimento_vfu_id');
            if (filled($vfuId) && VfuRegistration::query()->forActiveSito()->whereKey((int) $vfuId)->exists()) {
                $this->riferimentoVfuId = (string) (int) $vfuId;
            }

            $ordineId = request()->query('ordine_id');
            if (filled($ordineId)) {
                $this->prefillFromOrdine((int) $ordineId);
            } else {
                $this->addRiga();
            }
        }

        $this->ricalcolaTotali();
    }

    public function addRiga(): void
    {
        $this->righe[] = [
            'descrizione' => '',
            'quantita' => '1',
            'prezzo_unitario' => '0.00',
            'iva_percentuale' => (string) $this->ivaPercentuale,
        ];
    }

    public function removeRiga(int $index): void
    {
        unset($this->righe[$index]);
        $this->righe = array_values($this->righe);
        $this->ricalcolaTotali();
    }

    public function updatedRighe(): void
    {
        $this->ricalcolaTotali();
    }

    public function updatedIvaPercentuale(): void
    {
        $this->ricalcolaTotali();
    }

    private function ricalcolaTotali(): void
    {
        $imp = 0.0;
        foreach ($this->righe as $riga) {
            $qty = (float) ($riga['quantita'] ?? 1);
            $price = (float) ($riga['prezzo_unitario'] ?? 0);
            $imp += round($qty * $price, 2);
        }

        $this->imponibile = round($imp, 2);
        $this->ivaImporto = round($imp * ($this->ivaPercentuale / 100), 2);
        $this->totale = $this->imponibile + $this->ivaImporto;
    }

    public function save(FatturazioneService $service): void
    {
        $this->validate([
            'tipo' => 'required|in:fattura,nota_credito,preventivo',
            'anagraficaId' => 'required|exists:anagrafiche,id',
            'dataEmissione' => 'required|date',
            'dataScadenza' => 'nullable|date|after_or_equal:dataEmissione',
            'ivaPercentuale' => 'required|integer|min:0|max:100',
            'righe' => 'required|array|min:1',
            'righe.*.descrizione' => 'required|string',
            'righe.*.quantita' => 'required|numeric|min:0.001',
            'righe.*.prezzo_unitario' => 'required|numeric|min:0',
        ]);

        if ($this->fatturaId) {
            $fattura = Fattura::findOrFail($this->fatturaId);
            $this->authorize('update', $fattura);

            $fattura->update([
                'tipo' => $this->tipo,
                'anagrafica_id' => (int) $this->anagraficaId,
                'data_emissione' => $this->dataEmissione,
                'data_scadenza' => $this->dataScadenza ?: null,
                'iva_percentuale' => $this->ivaPercentuale,
                'note' => $this->note ?: null,
                'metodo_pagamento' => $this->metodoPagamento ?: null,
                'riferimento_vfu_id' => $this->riferimentoVfuId ? (int) $this->riferimentoVfuId : null,
                'ecommerce_ordine_id' => $this->ecommerceOrdineId,
            ]);

            $fattura->righe()->delete();
        } else {
            $this->authorize('create', Fattura::class);

            $fattura = $service->creaFattura([
                'tipo' => $this->tipo,
                'anagrafica_id' => (int) $this->anagraficaId,
                'data_emissione' => $this->dataEmissione,
                'data_scadenza' => $this->dataScadenza ?: null,
                'iva_percentuale' => $this->ivaPercentuale,
                'note' => $this->note ?: null,
                'metodo_pagamento' => $this->metodoPagamento ?: null,
                'riferimento_vfu_id' => $this->riferimentoVfuId ? (int) $this->riferimentoVfuId : null,
                'ecommerce_ordine_id' => $this->ecommerceOrdineId,
            ]);
        }

        foreach (array_values($this->righe) as $i => $rigaDati) {
            $service->aggiungiRiga($fattura, [
                'descrizione' => $rigaDati['descrizione'],
                'quantita' => (float) $rigaDati['quantita'],
                'prezzo_unitario' => (float) $rigaDati['prezzo_unitario'],
                'iva_percentuale' => (int) ($rigaDati['iva_percentuale'] ?? $this->ivaPercentuale),
                'ordine' => $i + 1,
            ]);
        }

        $this->redirect(route('segreteria.fatture.show', $fattura), navigate: true);
    }

    private function prefillFromOrdine(int $ordineId): void
    {
        $ordine = EcommerceOrdine::query()->with('user')->find($ordineId);

        if ($ordine === null) {
            $this->addRiga();

            return;
        }

        $this->ecommerceOrdineId = $ordine->id;
        $this->note = 'Fattura da ordine e-commerce #'.$ordine->id;

        if ($ordine->pagamento_metodo) {
            $this->metodoPagamento = str_replace('_', ' ', $ordine->pagamento_metodo);
        }

        $this->righe = collect($ordine->righe ?? [])
            ->map(fn (array $riga): array => [
                'descrizione' => trim(($riga['codice'] ?? '').' — '.($riga['nome'] ?? '')),
                'quantita' => (string) ($riga['qty'] ?? 1),
                'prezzo_unitario' => number_format((float) ($riga['prezzo_unitario'] ?? 0), 2, '.', ''),
                'iva_percentuale' => (string) $this->ivaPercentuale,
            ])
            ->values()
            ->all();

        if ($this->righe === []) {
            $this->addRiga();
        }
    }

    #[Computed]
    public function anagrafiche()
    {
        $query = Anagrafica::query()->orderBy('ragione_sociale');

        if ($this->anagraficaSearch !== '') {
            $term = '%'.$this->anagraficaSearch.'%';
            $query->where(function ($q) use ($term) {
                $q->where('ragione_sociale', 'like', $term)
                    ->orWhere('piva', 'like', $term);
            });
        }

        $results = $query->limit(20)->get(['id', 'ragione_sociale', 'piva']);

        if ($this->anagraficaId !== '' && ! $results->contains('id', (int) $this->anagraficaId)) {
            $selected = Anagrafica::query()
                ->whereKey((int) $this->anagraficaId)
                ->first(['id', 'ragione_sociale', 'piva']);

            if ($selected) {
                $results->prepend($selected);
            }
        }

        return $results;
    }

    #[Computed]
    public function vfuList()
    {
        $query = VfuRegistration::query()
            ->forActiveSito()
            ->orderByDesc('created_at');

        if ($this->vfuSearch !== '') {
            $term = '%'.$this->vfuSearch.'%';
            $query->where(function ($q) use ($term) {
                $q->where('targa', 'like', $term)
                    ->orWhere('telaio', 'like', $term)
                    ->orWhere('marca', 'like', $term)
                    ->orWhere('modello', 'like', $term);
            });
        }

        $results = $query->limit(20)->get(['id', 'targa', 'marca', 'modello', 'telaio']);

        if ($this->riferimentoVfuId !== '' && ! $results->contains('id', (int) $this->riferimentoVfuId)) {
            $selected = VfuRegistration::query()
                ->forActiveSito()
                ->whereKey((int) $this->riferimentoVfuId)
                ->first(['id', 'targa', 'marca', 'modello', 'telaio']);

            if ($selected) {
                $results->prepend($selected);
            }
        }

        return $results;
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.fatturazione.fattura-form',
            [],
            'fatturazione',
            $this->fatturaId ? 'Modifica Fattura' : 'Nuova Fattura',
            'Fatturazione',
        );
    }
}
