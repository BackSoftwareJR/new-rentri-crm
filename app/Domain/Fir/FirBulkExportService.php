<?php

namespace App\Domain\Fir;

use App\Models\Fir;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FirBulkExportService
{
    /** @var list<string> */
    private const DEFAULT_STATI = ['vidimato', 'firmato', 'trasmesso'];

    public function __construct(
        private FirService $fir,
    ) {}

    /**
     * @param  array{
     *   stato?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   q?: string|null
     * }  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        $query = $this->fir->filteredQuery($filters);

        if (empty($filters['stato'])) {
            $query->whereIn('stato', self::DEFAULT_STATI);
        }

        return $query;
    }

    /**
     * @return list<string>
     */
    public function rowFor(Fir $fir): array
    {
        $fir->loadMissing([
            'trasporto:id,codice_cer_id,stato',
            'trasporto.codiceCer:id,codice,descrizione',
        ]);

        return [
            $fir->numero_fir ?? sprintf('%s-%04d', $fir->codice_blocco, $fir->progressivo),
            $fir->codice_blocco,
            (string) $fir->progressivo,
            $fir->stato->value,
            $fir->vidimato_at?->format('Y-m-d H:i:s') ?? '',
            $fir->firmato_at?->format('Y-m-d H:i:s') ?? '',
            $fir->trasporto_id !== null ? (string) $fir->trasporto_id : '',
            $fir->trasporto?->codiceCer?->codice ?? '',
            $fir->trasporto?->codiceCer?->descrizione ?? '',
            $fir->trasporto?->stato?->value ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    public function exportCsv(array $filters = []): StreamedResponse
    {
        return response()->streamDownload(function () use ($filters) {
            $out = fopen('php://output', 'w');
            if ($out === false) {
                return;
            }

            fputcsv($out, [
                'numero_fir',
                'codice_blocco',
                'progressivo',
                'stato',
                'vidimato_at',
                'firmato_at',
                'trasporto_id',
                'codice_cer',
                'descrizione_cer',
                'trasporto_stato',
            ], ';');

            $this->filteredQuery($filters)
                ->chunkById(200, function ($firs) use ($out): void {
                    foreach ($firs as $fir) {
                        fputcsv($out, $this->rowFor($fir), ';');
                    }
                });

            fclose($out);
        }, $this->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filename(): string
    {
        return 'fir-bulk-'.now()->format('Y-m-d_His').'.csv';
    }
}
