<?php

namespace App\Http\Livewire\Segreteria\Fir;

use App\Domain\Fir\FirBloccoService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\FirBlocco;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Blocchi FIR')]
class FirBlocchiIndex extends SegreteriaPage
{
    use AuthorizesRequests;

    public string $codice_blocco = '';

    public string $num_iscr_sito = '';

    public function mount(): void
    {
        $this->authorize('viewAny', FirBlocco::class);
        $this->num_iscr_sito = RentriSetting::instance()->num_iscr_sito ?? '';
    }

    public function salvaBlocco(FirBloccoService $blocchi): void
    {
        $this->authorize('create', FirBlocco::class);

        $validated = $this->validate([
            'codice_blocco' => ['required', 'string', 'max:50'],
            'num_iscr_sito' => ['required', 'string', 'max:50'],
        ]);

        try {
            $blocchi->create($validated['codice_blocco'], $validated['num_iscr_sito']);
        } catch (\InvalidArgumentException $e) {
            $this->addError('codice_blocco', $e->getMessage());

            return;
        }

        $this->reset(['codice_blocco']);
        $this->num_iscr_sito = $validated['num_iscr_sito'];
        $this->resetValidation();

        session()->flash('success', 'Blocco FIR creato. I progressivi partono da zero.');
    }

    public function syncDaRentri(RentriFirBlocchiSyncInterface $sync): void
    {
        $this->authorize('create', FirBlocco::class);

        try {
            $result = $sync->sync();
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        session()->flash('success', sprintf(
            'Sync blocchi RENTRI: %d creati, %d aggiornati, %d invariati.',
            $result['created'],
            $result['updated'],
            $result['skipped'],
        ));
    }

    public function render(FirBloccoService $blocchi): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.fir.blocchi',
            [
                'blocchi'  => $blocchi->list(),
                'service'  => $blocchi,
            ],
            'fir',
            'Blocchi FIR',
        );
    }
}
