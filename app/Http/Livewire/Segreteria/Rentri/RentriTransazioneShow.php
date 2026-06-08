<?php

namespace App\Http\Livewire\Segreteria\Rentri;

use App\Domain\Rentri\RentriTransazioneRetryService;
use App\Domain\Rentri\RentriTransazioneService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\RentriTransazione;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Dettaglio transazione RENTRI')]
class RentriTransazioneShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public RentriTransazione $transazione;

    public function mount(RentriTransazione $transazione): void
    {
        $this->authorize('view', $transazione);
        $this->transazione = $transazione;
    }

    public function retryNow(RentriTransazioneRetryService $retryService): void
    {
        $this->authorize('view', $this->transazione);

        try {
            $retryService->retryNow($this->transazione);
        } catch (\RuntimeException $e) {
            session()->flash('error', $e->getMessage());

            return;
        }

        $this->transazione->refresh();
        session()->flash('success', 'Retry eseguito — controlla lo stato aggiornato.');
    }

    public function render(RentriTransazioneService $service): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.rentri.transazione-show',
            [
                'service'      => $service,
                'canRetryNow'  => $service->canRetryNow($this->transazione),
            ],
            'rentri',
            'Transazione '.$this->transazione->transazione_id,
        );
    }
}
