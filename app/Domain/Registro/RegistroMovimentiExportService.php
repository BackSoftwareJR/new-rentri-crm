<?php

namespace App\Domain\Registro;

use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RegistroMovimentiExportService
{
    public function __construct(
        private RegistroService $registro,
    ) {}

    /**
     * @param  array{
     *   codice_cer_id?: int|null,
     *   tipo?: string|null,
     *   data_da?: string|null,
     *   data_a?: string|null,
     *   q?: string|null
     * }  $filters
     */
    public function filteredQuery(array $filters = []): Builder
    {
        return $this->registro->filteredQuery($filters);
    }

    /**
     * @return list<array<string, string>>
     */
    public function rowFor(RegistroMovimento $movimento): array
    {
        $movimento->loadMissing(['codiceCer:id,codice,descrizione,um', 'source']);

        return [
            'id'               => (string) $movimento->id,
            'data_movimento'   => $movimento->data_movimento->format('Y-m-d H:i:s'),
            'tipo'             => $movimento->tipo->value,
            'codice_cer'       => $movimento->codiceCer?->codice ?? '',
            'descrizione_cer'  => $movimento->codiceCer?->descrizione ?? '',
            'peso_kg'          => (string) $movimento->peso_kg,
            'note'             => (string) ($movimento->note ?? ''),
            'rentri_trasmesso' => $movimento->rentri_trasmesso ? '1' : '0',
            'provenienza'      => $this->provenienzaLabel($movimento),
        ];
    }

    public function provenienzaLabel(RegistroMovimento $movimento): string
    {
        if ($movimento->source_type === null) {
            return 'Manuale';
        }

        $source = $movimento->source;

        return match ($movimento->source_type) {
            RegistroMovimento::SOURCE_VFU_REGISTRATION => $source instanceof VfuRegistration
                ? sprintf('VFU #%d - %s', $source->id, $source->targa)
                : 'Manuale',
            RegistroMovimento::SOURCE_TRASPORTO => $source instanceof Trasporto
                ? sprintf('Trasporto #%d', $source->id)
                : 'Trasporto',
            default => 'Manuale',
        };
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
                'id',
                'data_movimento',
                'tipo',
                'codice_cer',
                'descrizione_cer',
                'peso_kg',
                'note',
                'rentri_trasmesso',
                'provenienza',
            ], ';');

            $this->filteredQuery($filters)
                ->with('source')
                ->chunkById(200, function ($movimenti) use ($out): void {
                    foreach ($movimenti as $movimento) {
                        fputcsv($out, array_values($this->rowFor($movimento)), ';');
                    }
                });

            fclose($out);
        }, $this->filename(), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filename(): string
    {
        return 'registro-movimenti-'.now()->format('Y-m-d_His').'.csv';
    }
}
