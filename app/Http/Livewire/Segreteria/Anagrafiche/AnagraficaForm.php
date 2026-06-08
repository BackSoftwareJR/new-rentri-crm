<?php

namespace App\Http\Livewire\Segreteria\Anagrafiche;

use App\Domain\Anagrafiche\AnagraficaService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Http\Requests\Anagrafica\StoreAnagraficaRequest;
use App\Models\Anagrafica;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Anagrafica')]
class AnagraficaForm extends SegreteriaPage
{
    use AuthorizesRequests;

    public ?Anagrafica $anagrafica = null;

    public string $tipo = 'trasportatore';

    public string $ragione_sociale = '';

    public string $piva = '';

    public string $codice_fiscale = '';

    public string $codice_sdi = '';

    public string $pec = '';

    public string $email = '';

    public string $telefono = '';

    public string $indirizzo = '';

    public string $cap = '';

    public string $citta = '';

    public string $provincia = '';

    public string $note = '';

    public bool $gestisce_trasporti = false;

    public string $rentri_soggetto_id = '';

    /** @var array<int, array<string, mixed>> */
    public array $authorizations = [];

    public function mount(?Anagrafica $anagrafica = null): void
    {
        if ($anagrafica?->exists) {
            $this->authorize('update', $anagrafica);
            $anagrafica->load('authorizations');
            $this->anagrafica = $anagrafica;
            $this->fillFromModel($anagrafica);
        } else {
            $this->authorize('create', Anagrafica::class);
            $this->addAuthorizationRow();
        }
    }

    public function addAuthorizationRow(): void
    {
        $this->authorizations[] = [
            'id' => null,
            'numero' => '',
            'rilasciata_il' => '',
            'scade_il' => '',
            'tipo' => 'trasporto_rifiuti',
        ];
    }

    public function removeAuthorizationRow(int $index): void
    {
        unset($this->authorizations[$index]);
        $this->authorizations = array_values($this->authorizations);
    }

    public function save(AnagraficaService $service): void
    {
        $rules = StoreAnagraficaRequest::baseRules($this->anagrafica?->id);
        $validated = $this->validate($rules);

        $payload = collect($validated)->except('authorizations')->all();
        $authRows = $validated['authorizations'] ?? [];

        if ($this->anagrafica) {
            $this->authorize('update', $this->anagrafica);
            $service->update($this->anagrafica, $payload, $authRows);
            session()->flash('success', 'Anagrafica aggiornata.');
            $this->redirect(route('segreteria.anagrafiche.show', $this->anagrafica), navigate: true);
        } else {
            $this->authorize('create', Anagrafica::class);
            $created = $service->create($payload, $authRows);
            session()->flash('success', 'Anagrafica creata.');
            $this->redirect(route('segreteria.anagrafiche.show', $created), navigate: true);
        }
    }

    public function render(): View
    {
        $title = $this->anagrafica ? 'Modifica contatto' : 'Nuovo contatto';

        return $this->segreteriaView(
            'livewire.segreteria.anagrafiche.form',
            ['pageTitle' => $title],
            'anagrafiche',
            $title,
        );
    }

    private function fillFromModel(Anagrafica $anagrafica): void
    {
        $this->tipo = $anagrafica->tipo;
        $this->ragione_sociale = $anagrafica->ragione_sociale;
        $this->piva = $anagrafica->piva ?? '';
        $this->codice_fiscale = $anagrafica->codice_fiscale ?? '';
        $this->codice_sdi = $anagrafica->codice_sdi ?? '';
        $this->pec = $anagrafica->pec ?? '';
        $this->email = $anagrafica->email ?? '';
        $this->telefono = $anagrafica->telefono ?? '';
        $this->indirizzo = $anagrafica->indirizzo ?? '';
        $this->cap = $anagrafica->cap ?? '';
        $this->citta = $anagrafica->citta ?? '';
        $this->provincia = $anagrafica->provincia ?? '';
        $this->note = $anagrafica->note ?? '';
        $this->gestisce_trasporti = $anagrafica->gestisce_trasporti;
        $this->rentri_soggetto_id = $anagrafica->rentri_soggetto_id ?? '';

        $this->authorizations = $anagrafica->authorizations->map(fn ($auth) => [
            'id' => $auth->id,
            'numero' => $auth->numero,
            'rilasciata_il' => $auth->rilasciata_il?->format('Y-m-d') ?? '',
            'scade_il' => $auth->scade_il?->format('Y-m-d') ?? '',
            'tipo' => $auth->tipo,
        ])->all();

        if ($this->authorizations === []) {
            $this->addAuthorizationRow();
        }
    }
}
