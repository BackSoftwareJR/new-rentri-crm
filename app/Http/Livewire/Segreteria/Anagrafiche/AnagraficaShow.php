<?php

namespace App\Http\Livewire\Segreteria\Anagrafiche;

use App\Domain\Anagrafiche\AuthorizationComplianceService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\Anagrafica;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Exceptions\RentriApiException;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Dettaglio anagrafica')]
class AnagraficaShow extends SegreteriaPage
{
    use AuthorizesRequests;

    public Anagrafica $anagrafica;

    public bool $rentriVerificaLoading = false;

    /** @var array<string, mixed>|null */
    public ?array $rentriVerificaResult = null;

    public function mount(Anagrafica $anagrafica): void
    {
        $this->authorize('view', $anagrafica);
        $this->anagrafica = $anagrafica->load('authorizations');
    }

    public function verificaRentri(RentriApiClientInterface $apiClient): void
    {
        $this->authorize('update', $this->anagrafica);

        $identifier = $this->anagrafica->codice_fiscale ?: $this->anagrafica->piva;

        if (blank($identifier)) {
            $this->rentriVerificaResult = [
                'error' => 'Inserire CF o P.IVA per verificare su RENTRI.',
            ];

            return;
        }

        $this->rentriVerificaLoading = true;

        try {
            $result = $apiClient->lookupOperatore($identifier);

            $this->anagrafica->update([
                'rentri_verificato_at'      => now(),
                'rentri_iscrizione_numero'  => $result['numero_iscrizione'],
                'rentri_verificato_esito'   => $result['iscritto'] ? 'iscritto' : 'non_trovato',
            ]);

            $this->anagrafica->refresh();

            $this->rentriVerificaResult = $result;
        } catch (RentriApiException $e) {
            $this->rentriVerificaResult = ['error' => $e->getMessage()];
        } catch (\Throwable $e) {
            $this->rentriVerificaResult = ['error' => 'Errore durante la verifica RENTRI.'];
        } finally {
            $this->rentriVerificaLoading = false;
        }
    }

    public function canVerificaRentri(): bool
    {
        return in_array($this->anagrafica->tipo, ['trasportatore', 'impianto'], true);
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
