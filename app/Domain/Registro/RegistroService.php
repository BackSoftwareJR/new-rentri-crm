<?php

namespace App\Domain\Registro;

use App\Enums\RegistroMovimentoTipo;
use App\Models\RegistroMovimento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class RegistroService
{
    /**
     * @param  array{
     *   codice_cer_id?: int|null,
     *   tipo?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   q?: string|null,
     *   per_page?: int
     * }  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 25)));

        return $this->query($filters)
            ->paginate($perPage);
    }

    /**
     * @param  array{
     *   codice_cer_id?: int|null,
     *   tipo?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   q?: string|null
     * }  $filters
     * @return array{
     *   totale_movimenti: int,
     *   totale_carichi_kg: float,
     *   totale_scarichi_kg: float,
     *   saldo_kg: float,
     *   per_cer: list<array{codice_cer_id: int, codice: string, carichi_kg: float, scarichi_kg: float}>
     * }
     */
    public function aggregations(array $filters = []): array
    {
        $base = $this->query($filters);

        $totaleMovimenti = (clone $base)->count();

        $carichiKg = (float) (clone $base)
            ->where('tipo', RegistroMovimentoTipo::Carico)
            ->sum('peso_kg');

        $scarichiKg = (float) (clone $base)
            ->where('tipo', RegistroMovimentoTipo::Scarico)
            ->sum('peso_kg');

        $perCer = (clone $base)
            ->selectRaw('codice_cer_id')
            ->selectRaw("SUM(CASE WHEN tipo = 'carico' THEN peso_kg ELSE 0 END) as carichi_kg")
            ->selectRaw("SUM(CASE WHEN tipo = 'scarico' THEN peso_kg ELSE 0 END) as scarichi_kg")
            ->groupBy('codice_cer_id')
            ->with('codiceCer:id,codice,descrizione')
            ->get()
            ->map(fn (RegistroMovimento $row) => [
                'codice_cer_id' => (int) $row->codice_cer_id,
                'codice'        => $row->codiceCer?->codice ?? '—',
                'descrizione'   => $row->codiceCer?->descrizione ?? '',
                'carichi_kg'    => round((float) $row->carichi_kg, 4),
                'scarichi_kg'   => round((float) $row->scarichi_kg, 4),
            ])
            ->values()
            ->all();

        return [
            'totale_movimenti'  => $totaleMovimenti,
            'totale_carichi_kg' => round($carichiKg, 4),
            'totale_scarichi_kg'=> round($scarichiKg, 4),
            'saldo_kg'          => round($carichiKg - $scarichiKg, 4),
            'per_cer'           => $perCer,
        ];
    }

    /**
     * Cronologia movimenti per un codice CER (dettaglio serbatoio).
     *
     * @return \Illuminate\Support\Collection<int, RegistroMovimento>
     */
    public function cronologiaPerCer(int $codiceCerId, int $limit = 50)
    {
        return RegistroMovimento::query()
            ->forActiveSito()
            ->where('codice_cer_id', $codiceCerId)
            ->with('codiceCer:id,codice,descrizione,um')
            ->orderByDesc('data_movimento')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        return $this->query($filters);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = RegistroMovimento::query()
            ->forActiveSito()
            ->with('codiceCer:id,codice,descrizione,um,categoria');

        if (! empty($filters['codice_cer_id'])) {
            $query->where('codice_cer_id', (int) $filters['codice_cer_id']);
        }

        if (! empty($filters['tipo']) && in_array($filters['tipo'], ['carico', 'scarico'], true)) {
            $query->where('tipo', $filters['tipo']);
        }

        if (! empty($filters['data_da'])) {
            $query->where('data_movimento', '>=', Carbon::parse($filters['data_da'])->startOfDay());
        }

        if (! empty($filters['data_a'])) {
            $query->where('data_movimento', '<=', Carbon::parse($filters['data_a'])->endOfDay());
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('note', 'like', $term)
                    ->orWhereHas('codiceCer', fn (Builder $cer) => $cer
                        ->where('codice', 'like', $term)
                        ->orWhere('descrizione', 'like', $term));
            });
        }

        return $query->orderByDesc('data_movimento')->orderByDesc('id');
    }
}
