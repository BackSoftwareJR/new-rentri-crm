<?php

namespace App\Services\Rentri;

use App\Models\FirBlocco;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use Illuminate\Support\Facades\DB;

class RentriFirBlocchiSync implements RentriFirBlocchiSyncInterface
{
    public function __construct(
        private RentriApiClientInterface $apiClient,
    ) {}

    public function sync(): array
    {
        $response = $this->apiClient->fetchFirBlocchi();
        $items = $response['items'] ?? $response['elementi'] ?? $response['data'] ?? [];

        if (! is_array($items)) {
            $items = [];
        }

        $result = ['created' => 0, 'updated' => 0, 'skipped' => 0];
        $numIscrSito = RentriSetting::instance()->num_iscr_sito ?? '';

        return DB::transaction(function () use ($items, $numIscrSito, &$result) {
            foreach ($items as $item) {
                if (! is_array($item)) {
                    $result['skipped']++;

                    continue;
                }

                $codice = trim((string) ($item['codice_blocco'] ?? $item['codiceBlocco'] ?? ''));
                $sito = trim((string) ($item['num_iscr_sito'] ?? $item['numIscrSito'] ?? $numIscrSito));

                if ($codice === '' || $sito === '') {
                    $result['skipped']++;

                    continue;
                }

                $existing = FirBlocco::query()
                    ->where('codice_blocco', $codice)
                    ->where('num_iscr_sito', $sito)
                    ->first();

                if ($existing === null) {
                    FirBlocco::create([
                        'codice_blocco'      => $codice,
                        'num_iscr_sito'      => $sito,
                        'progressivo_ultimo' => $this->resolveProgressivo($item),
                    ]);
                    $result['created']++;

                    continue;
                }

                $progressivo = $this->resolveProgressivo($item);

                if ((int) $existing->progressivo_ultimo !== $progressivo) {
                    $existing->update(['progressivo_ultimo' => $progressivo]);
                    $result['updated']++;

                    continue;
                }

                $result['skipped']++;
            }

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function resolveProgressivo(array $item): int
    {
        return (int) ($item['progressivo_ultimo'] ?? $item['ultimo_progressivo'] ?? 0);
    }
}
