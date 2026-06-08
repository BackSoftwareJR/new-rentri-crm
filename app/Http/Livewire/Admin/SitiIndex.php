<?php

namespace App\Http\Livewire\Admin;

use App\Models\Sito;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

#[Title('Gestione impianti')]
class SitiIndex extends AdminPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url(as: 'q')]
    public string $search = '';

    public bool $showModal = false;

    public bool $isEditing = false;

    public ?int $editingSitoId = null;

    public string $formNome = '';

    public string $formIndirizzo = '';

    public string $formNumIscrSito = '';

    public string $formCfOperatore = '';

    public bool $formIsActive = true;

    public bool $formIsDefault = false;

    protected function rules(): array
    {
        return [
            'formNome' => ['required', 'string', 'max:200'],
            'formIndirizzo' => ['nullable', 'string', 'max:500'],
            'formNumIscrSito' => ['nullable', 'string', 'max:50'],
            'formCfOperatore' => ['nullable', 'string', 'max:16'],
            'formIsActive' => ['boolean'],
            'formIsDefault' => ['boolean'],
        ];
    }

    public function mount(): void
    {
        $this->authorize('viewAny', Sito::class);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function openCreateModal(): void
    {
        $this->authorize('create', Sito::class);
        $this->resetForm();
        $this->isEditing = false;
        $this->editingSitoId = null;
        $this->showModal = true;
    }

    public function openEditModal(int $sitoId): void
    {
        $sito = Sito::query()->findOrFail($sitoId);
        $this->authorize('update', $sito);

        $this->formNome = $sito->nome;
        $this->formIndirizzo = $sito->indirizzo ?? '';
        $this->formNumIscrSito = $sito->num_iscr_sito ?? '';
        $this->formCfOperatore = $sito->cf_operatore ?? '';
        $this->formIsActive = $sito->is_active;
        $this->formIsDefault = $sito->is_default;
        $this->isEditing = true;
        $this->editingSitoId = $sito->id;
        $this->showModal = true;
    }

    public function save(): void
    {
        $this->validate();

        $data = [
            'nome' => $this->formNome,
            'indirizzo' => $this->formIndirizzo !== '' ? $this->formIndirizzo : null,
            'num_iscr_sito' => $this->formNumIscrSito !== '' ? $this->formNumIscrSito : null,
            'cf_operatore' => $this->formCfOperatore !== '' ? $this->formCfOperatore : null,
            'is_active' => $this->formIsActive,
            'is_default' => $this->formIsDefault,
        ];

        if ($this->isEditing && $this->editingSitoId !== null) {
            $sito = Sito::query()->findOrFail($this->editingSitoId);
            $this->authorize('update', $sito);
            $sito->update($data);
        } else {
            $this->authorize('create', Sito::class);
            Sito::create($data);
        }

        if ($this->formIsDefault) {
            Sito::query()
                ->when($this->editingSitoId, fn ($q) => $q->where('id', '!=', $this->editingSitoId))
                ->update(['is_default' => false]);
        }

        $this->showModal = false;
        session()->flash('success', $this->isEditing ? 'Impianto aggiornato.' : 'Impianto creato.');
    }

    public function closeModal(): void
    {
        $this->showModal = false;
    }

    private function resetForm(): void
    {
        $this->formNome = '';
        $this->formIndirizzo = '';
        $this->formNumIscrSito = '';
        $this->formCfOperatore = '';
        $this->formIsActive = true;
        $this->formIsDefault = false;
    }

    public function render(): View
    {
        $query = Sito::query()->orderByDesc('is_default')->orderBy('nome');

        if ($this->search !== '') {
            $term = '%'.$this->search.'%';
            $query->where(function ($q) use ($term) {
                $q->where('nome', 'like', $term)
                    ->orWhere('num_iscr_sito', 'like', $term)
                    ->orWhere('indirizzo', 'like', $term);
            });
        }

        return $this->adminView(
            'livewire.admin.siti-index',
            ['siti' => $query->paginate(15)],
            'Impianti',
            'Admin',
            'Gestione impianti',
            'siti',
        );
    }
}
