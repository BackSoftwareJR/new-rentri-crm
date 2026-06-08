<?php

namespace App\Http\Livewire\Segreteria\CodiciCer;

use App\Domain\Magazzino\CodiceCerService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Http\Requests\CodiceCer\StoreCodiceCerRequest;
use App\Models\CodiceCer;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Codice CER')]
class CodiceCerForm extends SegreteriaPage
{
    use AuthorizesRequests;

    public ?CodiceCer $codiceCer = null;

    public string $codice = '';

    public string $descrizione = '';

    public string $categoria = 'altro';

    public string $um = 'kg';

    public ?string $limite_kg = null;

    public bool $attivo = true;

    public function mount(?CodiceCer $codiceCer = null): void
    {
        if ($codiceCer?->exists) {
            $this->authorize('update', $codiceCer);
            $this->codiceCer = $codiceCer;
            $this->codice = $codiceCer->codice;
            $this->descrizione = $codiceCer->descrizione;
            $this->categoria = $codiceCer->categoria;
            $this->um = $codiceCer->um;
            $this->limite_kg = $codiceCer->limite_kg !== null ? (string) $codiceCer->limite_kg : null;
            $this->attivo = $codiceCer->attivo;
        } else {
            $this->authorize('create', CodiceCer::class);
        }
    }

    public function save(CodiceCerService $service): void
    {
        $rules = StoreCodiceCerRequest::baseRules($this->codiceCer?->id);
        $validated = $this->validate($rules);

        if ($this->codiceCer) {
            $this->authorize('update', $this->codiceCer);
            $service->update($this->codiceCer, $validated);
            session()->flash('success', 'Codice CER aggiornato.');
            $this->redirect(route('segreteria.codici-cer.index'), navigate: true);
        } else {
            $this->authorize('create', CodiceCer::class);
            $service->create($validated);
            session()->flash('success', 'Codice CER creato.');
            $this->redirect(route('segreteria.codici-cer.index'), navigate: true);
        }
    }

    public function render(): View
    {
        $title = $this->codiceCer ? 'Modifica codice CER' : 'Nuovo codice CER';

        return $this->segreteriaView(
            'livewire.segreteria.codici-cer.form',
            ['pageTitle' => $title],
            'codici-cer',
            $title,
        );
    }
}
