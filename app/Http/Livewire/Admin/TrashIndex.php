<?php

namespace App\Http\Livewire\Admin;

use App\Models\Anagrafica;
use App\Models\Fattura;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;

#[Title('Cestino')]
class TrashIndex extends AdminPage
{
    use AuthorizesRequests;

    #[Url]
    public string $tab = 'vfu';

    public function mount(): void
    {
        $this->authorize('viewAny', VfuRegistration::class);
    }

    public function restoreVfu(int $id): void
    {
        $vfu = VfuRegistration::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $vfu);
        $vfu->restore();
        session()->flash('success', 'VFU ripristinato.');
    }

    public function restoreFattura(int $id): void
    {
        $fattura = Fattura::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $fattura);
        $fattura->restore();
        session()->flash('success', 'Fattura ripristinata.');
    }

    public function restoreAnagrafica(int $id): void
    {
        $anagrafica = Anagrafica::onlyTrashed()->findOrFail($id);
        $this->authorize('restore', $anagrafica);
        $anagrafica->restore();
        session()->flash('success', 'Anagrafica ripristinata.');
    }

    public function render(): View
    {
        $vfuItems = $this->tab === 'vfu'
            ? VfuRegistration::onlyTrashed()->orderByDesc('deleted_at')->limit(50)->get()
            : collect();

        $fatture = $this->tab === 'fatture'
            ? Fattura::onlyTrashed()->with('anagrafica')->orderByDesc('deleted_at')->limit(50)->get()
            : collect();

        $anagrafiche = $this->tab === 'anagrafiche'
            ? Anagrafica::onlyTrashed()->orderByDesc('deleted_at')->limit(50)->get()
            : collect();

        return $this->adminView(
            'livewire.admin.trash-index',
            [
                'vfuItems'    => $vfuItems,
                'fatture'     => $fatture,
                'anagrafiche' => $anagrafiche,
                'counts'      => [
                    'vfu'         => VfuRegistration::onlyTrashed()->count(),
                    'fatture'     => Fattura::onlyTrashed()->count(),
                    'anagrafiche' => Anagrafica::onlyTrashed()->count(),
                ],
            ],
            'Cestino',
            'Admin',
            'Cestino',
            'cestino',
        );
    }
}
