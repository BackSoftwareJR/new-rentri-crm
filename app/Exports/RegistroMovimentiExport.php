<?php

namespace App\Exports;

use App\Domain\Registro\RegistroMovimentiExportService;
use App\Enums\RegistroMovimentoTipo;
use App\Models\BonificaVfuMovimento;
use App\Models\RegistroMovimento;
use App\Models\Trasporto;
use App\Models\VfuRegistration;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class RegistroMovimentiExport implements FromCollection, WithHeadings, WithStyles
{
    /** @var list<array{row: int, tipo: RegistroMovimentoTipo}> */
    private array $styledRows = [];

    /**
     * @param  array<string, mixed>  $filters
     */
    public function __construct(
        private readonly RegistroMovimentiExportService $exportService,
        private readonly array $filters = [],
    ) {}

    public function collection(): Collection
    {
        $rows = collect();
        $rowIndex = 2;

        $this->exportService
            ->filteredQuery($this->filters)
            ->with(['codiceCer:id,codice,descrizione,um', 'source'])
            ->orderByDesc('data_movimento')
            ->chunkById(200, function ($movimenti) use (&$rows, &$rowIndex): void {
                foreach ($movimenti as $movimento) {
                    $this->styledRows[] = [
                        'row' => $rowIndex,
                        'tipo' => $movimento->tipo,
                    ];
                    $rows->push([
                        $movimento->data_movimento->format('d/m/Y H:i'),
                        $movimento->codiceCer?->codice ?? '',
                        $movimento->codiceCer?->descrizione ?? '',
                        ucfirst($movimento->tipo->value),
                        (float) $movimento->peso_kg,
                        (string) ($movimento->note ?? ''),
                        $this->rentriStatusLabel($movimento),
                        $this->sourceLinkText($movimento),
                    ]);
                    $rowIndex++;
                }
            });

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Data',
            'Codice CER',
            'Descrizione CER',
            'Tipo',
            'Peso (kg)',
            'Note',
            'Stato RENTRI',
            'Origine',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $styles = [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ],
            ],
        ];

        foreach ($this->styledRows as $styledRow) {
            $color = $styledRow['tipo'] === RegistroMovimentoTipo::Carico ? 'DCFCE7' : 'FEE2E2';

            $styles[$styledRow['row']] = [
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => $color],
                ],
            ];
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G', 'H'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $styles;
    }

    private function rentriStatusLabel(RegistroMovimento $movimento): string
    {
        if ($movimento->isLocked()) {
            return 'Bloccato';
        }

        if ($movimento->rentri_trasmesso) {
            return 'Trasmesso';
        }

        return 'Da trasmettere';
    }

    private function sourceLinkText(RegistroMovimento $movimento): string
    {
        $source = $movimento->source;

        return match ($movimento->source_type) {
            RegistroMovimento::SOURCE_VFU_REGISTRATION => $source instanceof VfuRegistration
                ? sprintf('VFU #%d — %s', $source->id, $source->targa)
                : 'Accettazione VFU',
            RegistroMovimento::SOURCE_TRASPORTO => $source instanceof Trasporto
                ? sprintf('Trasporto #%d', $source->id)
                : 'Trasporto',
            RegistroMovimento::SOURCE_BONIFICA_MOVIMENTO => $source instanceof BonificaVfuMovimento
                ? sprintf(
                    'Bonifica VFU #%d',
                    $source->bonifica?->vfu_registration_id ?? $source->bonifica_vfu_id,
                )
                : 'Bonifica VFU',
            RegistroMovimento::SOURCE_CARICO_MANUALE => 'Carico manuale',
            default => $movimento->source_type
                ? class_basename($movimento->source_type)
                : '—',
        };
    }
}
