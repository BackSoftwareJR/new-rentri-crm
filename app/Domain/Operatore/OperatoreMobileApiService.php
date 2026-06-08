<?php

namespace App\Domain\Operatore;

use App\Domain\Bonifica\BonificaService;
use App\Domain\Ecommerce\EcommerceService;
use App\Domain\Ecommerce\OperatoreFotoCatalogoService;
use App\Models\VfuRegistration;
use App\Support\Demo\DemoContext;

class OperatoreMobileApiService
{
    public function __construct(
        private readonly BonificaService $bonifica,
        private readonly EcommerceService $ecommerce,
        private readonly OperatoreFotoCatalogoService $fotoCatalogo,
    ) {}

    /**
     * @param  array{search?: string, filtro?: string}  $filters
     * @return array<string, mixed>
     */
    public function bonifica(array $filters = []): array
    {
        $veicoli = $this->bonifica->queryVeicoliDaBonificare($filters)
            ->get()
            ->map(fn (VfuRegistration $vfu) => $this->serializeVeicolo(
                $this->bonifica->enrichVeicolo($vfu),
            ));

        return $this->envelope([
            'count'   => $veicoli->count(),
            'veicoli' => $veicoli->values()->all(),
        ]);
    }

    /**
     * @param  array{categoria?: string|null, q?: string|null, per_page?: int}  $filters
     * @return array<string, mixed>
     */
    public function ricambi(array $filters = []): array
    {
        $filters['solo_disponibili'] = true;
        $paginator = $this->ecommerce->listProdotti($filters);

        $items = collect($paginator->items())
            ->map(fn ($prodotto) => $this->serializeProdotto($prodotto));

        return $this->envelope([
            'contatori'  => $this->ecommerce->contatoriCatalogo(),
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page'    => $paginator->lastPage(),
                'per_page'     => $paginator->perPage(),
                'total'        => $paginator->total(),
            ],
            'prodotti' => $items->values()->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function vetrina(int $limit = 12): array
    {
        $prodotti = $this->ecommerce->listProdottiInEvidenza($limit)
            ->map(fn ($prodotto) => $this->serializeProdotto($prodotto));

        return $this->envelope([
            'contatori' => $this->ecommerce->contatoriCatalogo(),
            'count'     => $prodotti->count(),
            'prodotti'  => $prodotti->values()->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function envelope(array $data): array
    {
        return array_merge([
            'api_version'  => 1,
            'demo_mode'    => DemoContext::isActive(),
            'generated_at' => now()->toIso8601String(),
        ], $data);
    }

    /**
     * @param  array<string, mixed>  $enriched
     * @return array<string, mixed>
     */
    private function serializeVeicolo(array $enriched): array
    {
        /** @var VfuRegistration $vfu */
        $vfu = $enriched['vfu'];

        return [
            'id'                            => $vfu->id,
            'targa'                         => $vfu->targa,
            'telaio'                        => $vfu->telaio,
            'marca'                         => $vfu->marca,
            'modello'                       => $vfu->modello,
            'stato'                         => $vfu->stato?->value ?? (string) $vfu->stato,
            'data_accettazione'             => $vfu->data_accettazione?->toDateString(),
            'bonifica_fase'                 => $enriched['bonifica_fase'] ?? null,
            'bonifica_giorni_alla_scadenza'=> $enriched['bonifica_giorni_alla_scadenza'] ?? null,
            'fase_corrente'                 => $enriched['fase_corrente'] ?? null,
            'bonifica_in_corso'             => $enriched['bonifica_in_corso'] !== null,
            'wizard_url'                    => route('operatore.bonifica.wizard', $vfu),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeProdotto(\App\Models\EcommerceProdotto $prodotto): array
    {
        $foto = $this->fotoCatalogo->fotoForProdotto($prodotto)
            ->map(fn ($f) => $this->fotoCatalogo->publicUrl($f))
            ->values()
            ->all();

        $vfu = $prodotto->vfuRegistration;

        return [
            'id'          => $prodotto->id,
            'codice'      => $prodotto->codice,
            'nome'        => $prodotto->nome,
            'categoria'   => $prodotto->categoria,
            'prezzo'      => (float) $prodotto->prezzo,
            'giacenza'    => (int) $prodotto->giacenza,
            'disponibile' => $prodotto->giacenza > 0,
            'foto_urls'   => $foto,
            'vfu'         => $vfu ? [
                'id'      => $vfu->id,
                'targa'   => $vfu->targa,
                'marca'   => $vfu->marca,
                'modello' => $vfu->modello,
            ] : null,
        ];
    }
}
