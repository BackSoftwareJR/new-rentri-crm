<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Vfu\SmontaggioService;
use App\Domain\Vfu\SmontaggioVetrinaService;
use App\Support\UploadValidation;
use App\Models\SmontaggioRicambio;
use App\Models\SmontaggioSession;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Rule;
use Livewire\Attributes\Title;
use Livewire\WithFileUploads;

#[Title('Wizard smontaggio')]
class SmontaggioWizard extends OperatorePage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public VfuRegistration $vfu;

    public ?int $sessionId = null;

    public int $step = 1;

    public ?string $errorMessage = null;

    public bool $success = false;

    // Step 2 — form per aggiungere ricambio
    #[Rule('required|string|max:500')]
    public string $nuovaDescrizione = '';

    #[Rule('nullable|string|max:100')]
    public string $nuovoNumeroParte = '';

    #[Rule('required|in:buono,accettabile,per_ricambi')]
    public string $nuovaCondizione = 'buono';

    #[Rule('nullable|numeric|min:0|max:99999')]
    public string $nuovoValore = '';

    public $nuovaFoto = null;

    // Step 3 — note finali
    #[Rule('nullable|string|max:2000')]
    public string $note = '';

    /** @var array<int, bool> */
    public array $pubblicaInVetrina = [];

    /** @var array<int, array{prodotto_id: int, nome: string}> */
    public array $vetrinaPubblicati = [];

    public function mount(VfuRegistration $vfu): void
    {
        $this->authorize('smontaggio.avvia', $vfu);

        $this->vfu = $vfu;

        $session = app(SmontaggioService::class)->avvia($vfu, auth()->user());
        $this->sessionId = $session->id;
        $this->note = $session->note ?? '';
    }

    public function swipeNext(): void
    {
        if ($this->success) {
            return;
        }

        if ($this->step < 3) {
            $this->goToStep($this->step + 1);
        }
    }

    public function swipePrev(): void
    {
        if ($this->success) {
            return;
        }

        if ($this->step > 1) {
            $this->goToStep($this->step - 1);
        }
    }

    public function goToStep(int $step): void
    {
        if ($step === 2 && $this->step === 1) {
            $this->step = 2;

            return;
        }

        if ($step === 3) {
            $this->step = 3;

            return;
        }

        if ($step < $this->step) {
            $this->step = $step;
        }
    }

    public function aggiungiRicambio(SmontaggioService $service): void
    {
        $this->authorize('smontaggio.gestisci', $this->getSession());

        $this->validate([
            'nuovaDescrizione' => ['required', 'string', 'max:500'],
            'nuovoNumeroParte' => ['nullable', 'string', 'max:100'],
            'nuovaCondizione' => ['required', 'in:buono,accettabile,per_ricambi'],
            'nuovoValore' => ['nullable', 'numeric', 'min:0', 'max:99999'],
            'nuovaFoto' => UploadValidation::smontaggioPhotoRules(),
        ]);

        $this->errorMessage = null;

        try {
            $service->aggiungiRicambio($this->getSession(), [
                'descrizione' => $this->nuovaDescrizione,
                'numero_parte' => $this->nuovoNumeroParte !== '' ? $this->nuovoNumeroParte : null,
                'condizione' => $this->nuovaCondizione,
                'valore_stimato' => $this->nuovoValore !== '' ? $this->nuovoValore : null,
                'foto' => $this->nuovaFoto,
            ]);

            $this->reset('nuovaDescrizione', 'nuovoNumeroParte', 'nuovaCondizione', 'nuovoValore', 'nuovaFoto');
            $this->nuovaCondizione = 'buono';
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function rimuoviRicambio(int $ricambioId, SmontaggioService $service): void
    {
        $this->authorize('smontaggio.gestisci', $this->getSession());

        $this->errorMessage = null;

        try {
            $service->rimuoviRicambio($this->getSession(), $ricambioId);
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function saveNote(): void
    {
        $this->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $this->getSession()->update(['note' => $this->note ?: null]);
        session()->flash('success', 'Note salvate.');
    }

    public function pubblicaSelezionatiInVetrina(SmontaggioVetrinaService $vetrina): void
    {
        $session = $this->getSession();
        $this->authorize('smontaggio.gestisci', $session);

        $selectedIds = collect($this->pubblicaInVetrina)
            ->filter(fn (bool $selected) => $selected)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        if ($selectedIds === []) {
            $this->errorMessage = 'Seleziona almeno un ricambio da pubblicare in vetrina.';

            return;
        }

        $this->errorMessage = null;

        $ricambi = SmontaggioRicambio::query()
            ->where('smontaggio_session_id', $session->id)
            ->whereIn('id', $selectedIds)
            ->get();

        foreach ($ricambi as $ricambio) {
            $prodotto = $vetrina->pubblicaInVetrina($ricambio);
            $this->vetrinaPubblicati[$ricambio->id] = [
                'prodotto_id' => $prodotto->id,
                'nome' => $prodotto->nome,
            ];
        }

        $count = $ricambi->count();
        session()->flash(
            'success',
            $count.' ricamb'.($count === 1 ? 'o inviato' : 'i inviati').' in vetrina come bozza (non visibile finché non attivato).',
        );
    }

    public function completa(SmontaggioService $service): void
    {
        $this->authorize('smontaggio.completa', $this->getSession());

        $this->validate(['note' => ['nullable', 'string', 'max:2000']]);

        $this->errorMessage = null;

        try {
            $session = $this->getSession();
            $session->update(['note' => $this->note ?: null]);
            $service->completa($session);
            $this->success = true;
        } catch (\Throwable $e) {
            $this->errorMessage = $e->getMessage();
        }
    }

    public function render(): View
    {
        $session = $this->sessionId
            ? SmontaggioSession::with(['ricambi'])->find($this->sessionId)
            : null;

        $ricambi = $session?->ricambi ?? collect();

        return $this->operatoreView(
            'livewire.operatore.smontaggio-wizard',
            compact('session', 'ricambi'),
            'smontaggio',
            $this->vfu->targa,
        );
    }

    private function getSession(): SmontaggioSession
    {
        if (! $this->sessionId) {
            throw new \RuntimeException('Nessuna sessione di smontaggio attiva.');
        }

        return SmontaggioSession::findOrFail($this->sessionId);
    }
}
