<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class BilancioCerExport implements FromCollection, WithHeadings, WithStyles
{
    /**
     * @param  array{rows: list<array<string,mixed>>, totals: array<string,mixed>}  $bilancio
     */
    public function __construct(
        private readonly array $bilancio,
    ) {}

    public function collection(): Collection
    {
        $rows = collect($this->bilancio['rows'])->map(fn (array $row): array => [
            $row['codice'],
            $row['descrizione'],
            $row['um'],
            (float) $row['carichi_kg'],
            (float) $row['scarichi_kg'],
            (float) $row['saldo_kg'],
            (int) $row['n_movimenti'],
        ]);

        $t = $this->bilancio['totals'];

        $rows->push([
            'TOTALE',
            '',
            '',
            (float) $t['carichi_kg'],
            (float) $t['scarichi_kg'],
            (float) $t['saldo_kg'],
            (int) $t['n_movimenti'],
        ]);

        return $rows;
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return [
            'Codice CER',
            'Descrizione',
            'UM',
            'Carichi (kg)',
            'Scarichi (kg)',
            'Saldo (kg)',
            'N. movimenti',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->bilancio['rows']) + 2;
        $dataEnd = $lastRow - 1;

        $styles = [
            1 => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8FAFC'],
                ],
            ],
            $lastRow => [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F1F5F9'],
                ],
            ],
        ];

        if ($dataEnd >= 2) {
            $sheet->getStyle("A2:A{$dataEnd}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_LEFT);

            $sheet->getStyle("D2:G{$lastRow}")
                ->getAlignment()
                ->setHorizontal(Alignment::HORIZONTAL_RIGHT);

            $sheet->getStyle("D2:F{$lastRow}")
                ->getNumberFormat()
                ->setFormatCode('#,##0.00');
        }

        foreach (['A', 'B', 'C', 'D', 'E', 'F', 'G'] as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        return $styles;
    }
}
