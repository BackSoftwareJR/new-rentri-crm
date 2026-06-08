<?php

namespace App\Http\Livewire\Segreteria\Anagrafiche;

use App\Domain\Anagrafiche\AuthorizationComplianceService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Anagrafica;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Dettaglio anagrafica')]
class AnagraficaShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public Anagrafica $anagrafica;

    public function mount(Anagrafica $anagrafica): void
    {
        $this->authorize('view', $anagrafica);
        $this->anagrafica = $anagrafica->load('authorizations');
    }

    public function render(AuthorizationComplianceService $compliance): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.anagrafiche.show',
            ['compliance' => $compliance],
            'anagrafiche',
            $this->anagrafica->ragione_sociale,
        );
    }
}
