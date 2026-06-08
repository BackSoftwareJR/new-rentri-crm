<?php

namespace App\Services\Rentri;

use App\Models\CodiceCer;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Illuminate\Support\Facades\DB;

class RentriCodificheSync implements RentriCodificheSyncInterface
{
    public function __construct(
        protected RentriApiClientInterface $apiClient,
    ) {}

    public function sync(): array
    {
        $catalogo = $this->apiClient->request('GET', '/codifiche/cer', []);
        $items = $catalogo['items'] ?? [];

        $result = [
            'created'            => 0,
            'updated'            => 0,
            'deactivated'        => 0,
            'skipped'            => 0,
            'created_codes'      => [],
            'updated_codes'      => [],
            'deactivated_codes'  => [],
        ];

        $remoteCodes = [];

        return DB::transaction(function () use ($items, &$result, &$remoteCodes) {
            foreach ($items as $item) {
                $codice = isset($item['codice']) ? trim((string) $item['codice']) : '';
                if ($codice === '') {
                    $result['skipped']++;

                    continue;
                }

                $remoteCodes[] = $codice;
                $attributes = $this->mapItemToAttributes($item);
                $existing = CodiceCer::query()->where('codice', $codice)->first();

                if ($existing === null) {
                    CodiceCer::create(array_merge(['codice' => $codice], $attributes));
                    $result['created']++;
                    $result['created_codes'][] = $codice;

                    continue;
                }

                if ($this->hasChanges($existing, $attributes)) {
                    $existing->update($attributes);
                    $result['updated']++;
                    $result['updated_codes'][] = $codice;
                } else {
                    $result['skipped']++;
                }
            }

            $this->deactivateMissingRentriCodes($remoteCodes, $result);

            return $result;
        });
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    protected function mapItemToAttributes(array $item): array
    {
        return [
            'descrizione'       => (string) ($item['descrizione'] ?? ''),
            'categoria'         => ($item['pericoloso'] ?? false) ? 'pericoloso' : 'altro',
            'um'                => (string) ($item['um'] ?? 'kg'),
            'rentri_codice_ref' => $item['rentri_ref'] ?? null,
            'attivo'            => (bool) ($item['attivo'] ?? true),
        ];
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    protected function hasChanges(CodiceCer $existing, array $attributes): bool
    {
        foreach ($attributes as $key => $value) {
            $current = $existing->{$key};

            if ($key === 'attivo') {
                if ((bool) $current !== (bool) $value) {
                    return true;
                }

                continue;
            }

            if ((string) ($current ?? '') !== (string) ($value ?? '')) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param  list<string>  $remoteCodes
     * @param  array<string, mixed>  $result
     */
    protected function deactivateMissingRentriCodes(array $remoteCodes, array &$result): void
    {
        if ($remoteCodes === []) {
            return;
        }

        CodiceCer::query()
            ->whereNotNull('rentri_codice_ref')
            ->whereNotIn('codice', $remoteCodes)
            ->where('attivo', true)
            ->orderBy('codice')
            ->each(function (CodiceCer $cer) use (&$result) {
                $cer->update(['attivo' => false]);
                $result['deactivated']++;
                $result['deactivated_codes'][] = $cer->codice;
            });
    }
}
