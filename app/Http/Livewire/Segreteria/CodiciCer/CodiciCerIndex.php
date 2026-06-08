<?php

namespace App\Http\Livewire\Segreteria\CodiciCer;

use App\Domain\Magazzino\CodiceCerService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\CodiceCer;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\Title;

#[Title('Codici CER')]
class CodiciCerIndex extends SegreteriaPage
{
    use AuthorizesRequests;

    public bool $showModal = false;

    public ?int $editingId = null;

    public string $codice = '';

    public string $descrizione = '';

    public string $categoria = 'altro';

    public string $um = 'kg';

    public ?string $limite_kg = null;

    public bool $attivo = true;

    public function mount(): void
    {
        $this->authorize('viewAny', CodiceCer::class);
    }

    public function openCreate(): void
    {
        $this->authorize('create', CodiceCer::class);
        $this->resetForm();
        $this->showModal = true;
    }

    public function openEdit(int $id, CodiceCerService $service): void
    {
        $codice = $service->find($id);
        $this->authorize('update', $codice);
        $this->editingId = $codice->id;
        $this->codice = $codice->codice;
        $this->descrizione = $codice->descrizione;
        $this->categoria = $codice->categoria;
        $this->um = $codice->um;
        $this->limite_kg = $codice->limite_kg !== null ? (string) $codice->limite_kg : null;
        $this->attivo = $codice->attivo;
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->resetForm();
    }

    public function save(CodiceCerService $service): void
    {
        $rules = \App\Http\Requests\CodiceCer\StoreCodiceCerRequest::baseRules($this->editingId);
        $validated = $this->validate($rules);

        if ($this->editingId) {
            $codice = $service->find($this->editingId);
            $this->authorize('update', $codice);
            $service->update($codice, $validated);
            session()->flash('success', 'Codice CER aggiornato.');
        } else {
            $this->authorize('create', CodiceCer::class);
            $service->create($validated);
            session()->flash('success', 'Codice CER creato.');
        }

        $this->closeModal();
    }

    public function syncDaRentri(RentriCodificheSyncInterface $sync): void
    {
        Gate::authorize('codice-cer.sync-rentri');

        try {
            $result = $sync->sync();
        } catch (\RuntimeException $e) {
            Log::channel('rentri')->error('CodiciCer sync failed', ['error' => $e->getMessage()]);
            session()->flash('error', 'Errore durante la sincronizzazione RENTRI: '.$e->getMessage());

            return;
        }

        Log::channel('rentri')->info('CodiciCer sync completata', [
            'created'     => $result['created'],
            'updated'     => $result['updated'],
            'deactivated' => $result['deactivated'],
            'skipped'     => $result['skipped'],
        ]);

        session()->flash('success', sprintf(
            'Sincronizzati: %d creati, %d aggiornati, %d disattivati.',
            $result['created'],
            $result['updated'],
            $result['deactivated'],
        ));
    }

    public function delete(int $id, CodiceCerService $service): void
    {
        $codice = $service->find($id);
        $this->authorize('delete', $codice);
        $action = $service->delete($codice);
        $message = $action === 'deactivated'
            ? 'Codice CER disattivato (presenti movimenti).'
            : 'Codice CER eliminato.';
        session()->flash('success', $message);
    }

    public function render(CodiceCerService $service): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.codici-cer.index',
            ['codici' => $service->query()->get()],
            'codici-cer',
            'Codici CER',
        );
    }

    private function resetForm(): void
    {
        $this->editingId = null;
        $this->codice = '';
        $this->descrizione = '';
        $this->categoria = 'altro';
        $this->um = 'kg';
        $this->limite_kg = null;
        $this->attivo = true;
        $this->resetValidation();
    }
}
