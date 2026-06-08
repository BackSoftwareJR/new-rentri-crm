<?php

namespace App\Domain\Mud;

use App\Domain\Audit\ActivityLogService;
use App\Enums\MudStato;
use App\Enums\RegistroMovimentoTipo;
use App\Models\MudDichiarazione;
use App\Models\RegistroMovimento;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;

class MudService
{
    /**
     * @param  array{stato?: string|null, anno_riferimento?: int|null, per_page?: int}  $filters
     */
    public function list(array $filters = []): LengthAwarePaginator
    {
        $perPage = max(10, min(100, (int) ($filters['per_page'] ?? 25)));

        return $this->query($filters)->paginate($perPage);
    }

    /**
     * @param  array{stato?: string|null, anno_riferimento?: int|null}  $filters
     * @return array{totale: int, bozze: int, completate: int, inviate: int}
     */
    public function contatori(array $filters = []): array
    {
        $base = $this->query(array_diff_key($filters, ['stato' => null]));

        return [
            'totale'     => (clone $base)->count(),
            'bozze'      => (clone $base)->where('stato', MudStato::Bozza)->count(),
            'completate' => (clone $base)->where('stato', MudStato::Completata)->count(),
            'inviate'    => (clone $base)->where('stato', MudStato::Inviata)->count(),
        ];
    }

    public function createBozza(int $anno, int $userId): MudDichiarazione
    {
        if ($anno < 2000 || $anno > (int) now()->format('Y') + 1) {
            throw new \InvalidArgumentException('Anno di riferimento non valido.');
        }

        if (MudDichiarazione::query()->where('anno_riferimento', $anno)->exists()) {
            throw new \InvalidArgumentException('Esiste già una dichiarazione MUD per l\'anno '.$anno.'.');
        }

        $righe = $this->aggregateRighePerAnno($anno);

        return MudDichiarazione::create([
            'anno_riferimento' => $anno,
            'stato'            => MudStato::Bozza,
            'righe'            => $righe,
            'user_id'          => $userId,
        ]);
    }

    public function completa(MudDichiarazione $dichiarazione): MudDichiarazione
    {
        if ($dichiarazione->stato !== MudStato::Bozza) {
            throw new \InvalidArgumentException('Solo le bozze possono essere completate.');
        }

        $export = $this->buildExportPayload($dichiarazione);

        $dichiarazione->update([
            'stato'          => MudStato::Completata,
            'export_payload' => $export,
            'completata_at'  => now(),
        ]);

        $fresh = $dichiarazione->fresh();

        app(ActivityLogService::class)->record(
            'mud',
            'Dichiarazione MUD completata',
            $fresh,
            [
                'anno_riferimento' => $fresh->anno_riferimento,
                'righe_count'      => count($fresh->righe ?? []),
            ],
            $fresh->user_id,
        );

        return $fresh;
    }

    /**
     * @return array<string, mixed>
     */
    public function buildExportPayload(MudDichiarazione $dichiarazione): array
    {
        $righe = $dichiarazione->righe ?? [];

        return [
            'formato'          => 'mud-json-stub-v1',
            'xml_schema'       => MudXmlValidationService::SCHEMA_VERSION,
            'anno_riferimento' => $dichiarazione->anno_riferimento,
            'generato_il'      => now()->toIso8601String(),
            'operatore'        => [
                'stub' => true,
            ],
            'righe'            => $righe,
            'totali'           => [
                'carichi_kg'  => round(collect($righe)->sum('carichi_kg'), 4),
                'scarichi_kg' => round(collect($righe)->sum('scarichi_kg'), 4),
                'saldo_kg'    => round(collect($righe)->sum('saldo_kg'), 4),
                'codici_cer'  => count($righe),
            ],
            'note'             => 'Export stub — invio telematico via MudInvioTelematicoService (protocollo simulato).',
        ];
    }

    public function exportFilename(MudDichiarazione $dichiarazione): string
    {
        return sprintf('mud-%d-stub.json', $dichiarazione->anno_riferimento);
    }

    public function statoBadgeVariant(MudStato $stato): string
    {
        return match ($stato) {
            MudStato::Inviata    => 'success',
            MudStato::Completata => 'info',
            MudStato::Bozza      => 'warning',
        };
    }

    public function statoLabel(MudStato $stato): string
    {
        return match ($stato) {
            MudStato::Inviata    => 'Inviata',
            MudStato::Completata => 'Completata',
            MudStato::Bozza      => 'Bozza',
        };
    }

    /**
     * @return list<array{
     *   codice_cer_id: int,
     *   codice: string,
     *   descrizione: string,
     *   carichi_kg: float,
     *   scarichi_kg: float,
     *   saldo_kg: float
     * }>
     */
    public function aggregateRighePerAnno(int $anno): array
    {
        $da = Carbon::create($anno, 1, 1)->startOfDay();
        $a = Carbon::create($anno, 12, 31)->endOfDay();

        return RegistroMovimento::query()
            ->whereBetween('data_movimento', [$da, $a])
            ->select('codice_cer_id')
            ->selectRaw("SUM(CASE WHEN tipo = ? THEN peso_kg ELSE 0 END) as carichi_kg", [RegistroMovimentoTipo::Carico->value])
            ->selectRaw("SUM(CASE WHEN tipo = ? THEN peso_kg ELSE 0 END) as scarichi_kg", [RegistroMovimentoTipo::Scarico->value])
            ->groupBy('codice_cer_id')
            ->with('codiceCer:id,codice,descrizione')
            ->orderBy('codice_cer_id')
            ->get()
            ->map(function (RegistroMovimento $row) {
                $carichi = round((float) $row->carichi_kg, 4);
                $scarichi = round((float) $row->scarichi_kg, 4);

                return [
                    'codice_cer_id' => (int) $row->codice_cer_id,
                    'codice'        => $row->codiceCer?->codice ?? '—',
                    'descrizione'   => $row->codiceCer?->descrizione ?? '',
                    'carichi_kg'    => $carichi,
                    'scarichi_kg'   => $scarichi,
                    'saldo_kg'      => round($carichi - $scarichi, 4),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function query(array $filters): Builder
    {
        $query = MudDichiarazione::query()->with('user:id,name');

        if (! empty($filters['stato']) && in_array($filters['stato'], ['bozza', 'completata', 'inviata'], true)) {
            $query->where('stato', $filters['stato']);
        }

        if (! empty($filters['anno_riferimento'])) {
            $query->where('anno_riferimento', (int) $filters['anno_riferimento']);
        }

        return $query->orderByDesc('anno_riferimento');
    }
}
