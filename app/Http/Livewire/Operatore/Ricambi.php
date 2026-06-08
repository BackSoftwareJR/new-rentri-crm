<?php

namespace App\Http\Livewire\Operatore;

use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Ecommerce\OperatoreFotoCatalogoService;
use App\Models\EcommerceProdotto;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Title('Ricambi disponibili')]
class Ricambi extends OperatorePage
{
    use AuthorizesRequests;
    use WithFileUploads;
    use WithPagination;

    /** @var list<\Livewire\Features\SupportFileUploads\TemporaryUploadedFile> */
    public $fotoBulk = [];

    public ?int $prodottoSelezionato = null;

    #[Url]
    public string $categoria = '';

    #[Url(as: 'q')]
    public string $search = '';

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

    public function uploadFotoBulk(OperatoreFotoCatalogoService $fotoService): void
    {
        $this->authorize('uploadPhotos', EcommerceProdotto::class);

        $this->validate([
            'prodottoSelezionato' => ['required', 'integer', 'exists:ecommerce_prodotti,id'],
            'fotoBulk'            => ['required', 'array', 'min:1', 'max:10'],
            'fotoBulk.*'          => ['image', 'max:2048'],
        ], [
            'prodottoSelezionato.required' => 'Seleziona un ricambio dal catalogo.',
        ]);

        $prodotto = EcommerceProdotto::query()->findOrFail($this->prodottoSelezionato);
        $this->authorize('linkPhoto', $prodotto);

        $user = auth()->user();
        abort_unless($user !== null, 403);

        $linked = $fotoService->linkBulk($prodotto, $this->fotoBulk, $user);
        $count = count($linked);

        $this->reset('fotoBulk');

        session()->flash('success', "{$count} foto collegate a {$prodotto->codice} — {$prodotto->nome}.");
    }

    public function render(EcommerceService $ecommerce, OperatoreFotoCatalogoService $fotoService): View
    {
        $filters = array_filter([
            'categoria'        => $this->categoria !== '' ? $this->categoria : null,
            'q'                => $this->search !== '' ? $this->search : null,
            'solo_disponibili' => true,
        ], fn ($v) => $v !== null && $v !== '');

        $prodotti = $ecommerce->listProdotti($filters);
        $fotoPerProdotto = [];

        foreach ($prodotti as $prodotto) {
            $fotoPerProdotto[$prodotto->id] = $fotoService->fotoForProdotto($prodotto)
                ->map(fn ($foto) => $fotoService->publicUrl($foto))
                ->all();
        }

        return $this->operatoreView(
            'livewire.operatore.ricambi',
            [
                'prodotti'          => $prodotti,
                'contatori'         => $ecommerce->contatoriCatalogo(),
                'service'           => $ecommerce,
                'fotoPerProdotto'   => $fotoPerProdotto,
            ],
            'ricambi',
            'Ricambi',
        );
    }
}
