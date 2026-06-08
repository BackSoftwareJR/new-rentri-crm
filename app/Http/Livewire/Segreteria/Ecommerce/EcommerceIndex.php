<?php

namespace App\Http\Livewire\Segreteria\Ecommerce;

use App\Domain\Ecommerce\EcommerceProdottoImmagineService;
use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Ecommerce\StripeDisputeStubService;
use App\Domain\Ecommerce\StripeProductionSwitchService;
use App\Domain\Ecommerce\StripeReconciliationReportService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithPagination;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('E-commerce ricambi')]
class EcommerceIndex extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithPagination;

    #[Url]
    public string $categoria = '';

    #[Url(as: 'q')]
    public string $search = '';

    #[Url(as: 'stato')]
    public string $statoOrdine = '';

    public function mount(): void
    {
        $this->authorize('viewAny', EcommerceProdotto::class);
    }

    public function updatedCategoria(): void
    {
        $this->resetPage();
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function updatedStatoOrdine(): void
    {
        // filtro ordini recenti — nessuna paginazione condivisa
    }

    public function exportReconciliationCsv(): StreamedResponse
    {
        $this->authorize('viewAny', EcommerceProdotto::class);

        $csv = app(StripeReconciliationReportService::class)->toCsv();
        $filename = 'stripe-reconciliation-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(
            static function () use ($csv): void {
                echo $csv;
            },
            $filename,
            ['Content-Type' => 'text/csv; charset=UTF-8'],
        );
    }

    public function render(EcommerceService $ecommerce, EcommerceProdottoImmagineService $immagini): View
    {
        $filters = array_filter([
            'categoria' => $this->categoria !== '' ? $this->categoria : null,
            'q'         => $this->search !== '' ? $this->search : null,
        ], fn ($v) => $v !== null && $v !== '');

        $ordiniFilters = array_filter([
            'stato' => $this->statoOrdine !== '' ? $this->statoOrdine : null,
        ], fn ($v) => $v !== null && $v !== '');

        $stripeSwitch = app(StripeProductionSwitchService::class);
        $reconciliation = app(StripeReconciliationReportService::class);

        return $this->segreteriaView(
            'livewire.segreteria.ecommerce.index',
            [
                'prodotti'              => $ecommerce->listProdotti($filters),
                'ordini'                => $ecommerce->recentOrdini($ordiniFilters),
                'contatori'             => $ecommerce->contatoriCatalogo(),
                'cartCount'             => $ecommerce->cartCount(),
                'service'               => $ecommerce,
                'immagini'              => $immagini,
                'stripeSwitch'          => $stripeSwitch->summary(),
                'stripeChecklist'       => $stripeSwitch->unifiedChecklist(),
                'stripeRollback'        => $stripeSwitch->rollbackSteps(),
                'reconciliationSummary' => $reconciliation->summary(),
                'reconciliationRows'    => $reconciliation->rows()->take(10),
                'disputeWorkflow'       => app(StripeDisputeStubService::class)->workflowSteps(),
            ],
            'ecommerce',
            'E-commerce',
        );
    }
}
