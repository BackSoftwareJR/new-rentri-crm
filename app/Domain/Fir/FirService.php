<?php

namespace App\Domain\Fir;

use App\Domain\Trasporti\TrasportoTrackingService;
use App\Enums\TrasportoStato;
use App\Models\Fir;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;

class FirService
{
    public function __construct(
        private TrasportoTrackingService $tracking,
    ) {}

    /**
     * @param  array{stato?: string|null, q?: string|null, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 25)));

        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @param  array{stato?: string|null, q?: string|null, data_da?: string|null, data_a?: string|null}  $filters
     * @return array{totale: int, vidimati: int, firmati: int, tracking_stub: int}
     */
    public function contatori(array $filters = []): array
    {
        $base = $this->query(array_diff_key($filters, ['stato' => null]));

        $trackingStub = (clone $base)
            ->whereHas('trasporto', fn (Builder $q) => $q->where('stato', TrasportoStato::InTransito))
            ->count();

        return [
            'totale'         => (clone $base)->count(),
            'vidimati'       => (clone $base)->where('stato', 'vidimato')->count(),
            'firmati'        => (clone $base)->where('stato', 'firmato')->count(),
            'tracking_stub'  => $trackingStub,
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        return $this->query($filters);
    }

    public function hasTrackingStub(Fir $fir): bool
    {
        $trasporto = $fir->trasporto;

        return $trasporto !== null && $this->tracking->isTrackingAvailable($trasporto);
    }

    public function numeroDisplay(Fir $fir): string
    {
        if ($fir->numero_fir) {
            return $fir->numero_fir;
        }

        return sprintf('%s-%04d', $fir->codice_blocco, $fir->progressivo);
    }

    public function statoBadgeVariant(string $stato): string
    {
        return match ($stato) {
            'vidimato'   => 'success',
            'firmato'    => 'success',
            'trasmesso'  => 'info',
            'annullato'  => 'muted',
            default      => 'warning',
        };
    }

    public function statoLabel(string $stato): string
    {
        return match ($stato) {
            'vidimato'  => 'Vidimato',
            'firmato'   => 'Firmato xFIR',
            'trasmesso' => 'Trasmesso',
            'annullato' => 'Annullato',
            default     => 'Bozza',
        };
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = Fir::query()
            ->with([
                'trasporto:id,codice_cer_id,stato',
                'trasporto.codiceCer:id,codice,descrizione',
                'blocco:codice_blocco,num_iscr_sito',
            ]);

        if (! empty($filters['stato'])) {
            $query->where('stato', $filters['stato']);
        }

        if (! empty($filters['data_da'])) {
            $query->whereDate('vidimato_at', '>=', $filters['data_da']);
        }

        if (! empty($filters['data_a'])) {
            $query->whereDate('vidimato_at', '<=', $filters['data_a']);
        }

        $q = isset($filters['q']) ? trim((string) $filters['q']) : '';
        if ($q !== '') {
            $term = '%'.addcslashes($q, '%_\\').'%';
            $query->where(function (Builder $sub) use ($term) {
                $sub->where('numero_fir', 'like', $term)
                    ->orWhere('codice_blocco', 'like', $term)
                    ->orWhereHas('trasporto.codiceCer', fn (Builder $cer) => $cer
                        ->where('codice', 'like', $term));
            });
        }

        return $query->orderByDesc('vidimato_at')->orderByDesc('id');
    }
}
