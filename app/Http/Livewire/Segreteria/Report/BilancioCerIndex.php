<?php

namespace App\Http\Livewire\Segreteria\Report;

use App\Exports\BilancioCerExport;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\RegistroMovimento;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Title('Bilancio CER')]
class BilancioCerIndex extends SegreteriaPage
{
    use AuthorizesRequests;

    private const MAX_RANGE_DAYS = 365;

    #[Url]
    public string $preset = 'year';

    #[Url]
    public string $data_da = '';

    #[Url]
    public string $data_a = '';

    public function mount(): void
    {
        $this->authorize('viewAny', RegistroMovimento::class);

        if ($this->preset !== 'custom' || ($this->data_da === '' && $this->data_a === '')) {
            $this->applyPreset($this->preset);
        }

        $this->enforceDateRange();
    }

    public function updatedDataDa(): void
    {
        $this->enforceDateRange();
    }

    public function updatedDataA(): void
    {
        $this->enforceDateRange();
    }

    public function updatedPreset(string $value): void
    {
        if ($value !== 'custom') {
            $this->applyPreset($value);
        }
    }

    public function applyPreset(string $preset): void
    {
        $now = Carbon::now();

        match ($preset) {
            'year' => [$this->data_da, $this->data_a] = [
                $now->copy()->startOfYear()->toDateString(),
                $now->copy()->endOfYear()->toDateString(),
            ],
            'q1' => [$this->data_da, $this->data_a] = [
                $now->copy()->setMonth(1)->startOfMonth()->toDateString(),
                $now->copy()->setMonth(3)->endOfMonth()->toDateString(),
            ],
            'q2' => [$this->data_da, $this->data_a] = [
                $now->copy()->setMonth(4)->startOfMonth()->toDateString(),
                $now->copy()->setMonth(6)->endOfMonth()->toDateString(),
            ],
            'q3' => [$this->data_da, $this->data_a] = [
                $now->copy()->setMonth(7)->startOfMonth()->toDateString(),
                $now->copy()->setMonth(9)->endOfMonth()->toDateString(),
            ],
            'q4' => [$this->data_da, $this->data_a] = [
                $now->copy()->setMonth(10)->startOfMonth()->toDateString(),
                $now->copy()->setMonth(12)->endOfMonth()->toDateString(),
            ],
            'month' => [$this->data_da, $this->data_a] = [
                $now->copy()->startOfMonth()->toDateString(),
                $now->copy()->endOfMonth()->toDateString(),
            ],
            default => null,
        };

        $this->preset = $preset;
        $this->enforceDateRange();
    }

    private function enforceDateRange(): void
    {
        if ($this->data_da === '' || $this->data_a === '') {
            return;
        }

        $start = Carbon::parse($this->data_da)->startOfDay();
        $end = Carbon::parse($this->data_a)->startOfDay();

        if ($end->lt($start)) {
            $this->data_a = $start->toDateString();

            return;
        }

        if ($start->diffInDays($end) > self::MAX_RANGE_DAYS) {
            $this->data_a = $start->copy()->addDays(self::MAX_RANGE_DAYS)->toDateString();
            session()->flash('warning', 'Periodo massimo '.self::MAX_RANGE_DAYS.' giorni.');
        }
    }

    /**
     * @return array{rows: list<array<string,mixed>>, totals: array<string,mixed>}
     */
    #[Computed]
    public function bilancio(): array
    {
        $aggregatedRows = RegistroMovimento::query()
            ->forActiveSito()
            ->when($this->data_da !== '', fn (Builder $q) => $q->whereDate('data_movimento', '>=', $this->data_da))
            ->when($this->data_a !== '', fn (Builder $q) => $q->whereDate('data_movimento', '<=', $this->data_a))
            ->selectRaw('codice_cer_id')
            ->selectRaw("SUM(CASE WHEN tipo = 'carico' THEN peso_kg ELSE 0 END) as carichi_kg")
            ->selectRaw("SUM(CASE WHEN tipo = 'scarico' THEN peso_kg ELSE 0 END) as scarichi_kg")
            ->selectRaw('COUNT(*) as n_movimenti')
            ->groupBy('codice_cer_id')
            ->with('codiceCer:id,codice,descrizione,um')
            ->get();

        $rows = $aggregatedRows
            ->map(function (RegistroMovimento $row): array {
                $cer = $row->codiceCer;

                return [
                    'id' => (int) $row->codice_cer_id,
                    'codice' => $cer?->codice ?? '—',
                    'descrizione' => $cer?->descrizione ?? '—',
                    'um' => $cer?->um ?? 'kg',
                    'carichi_kg' => round((float) $row->carichi_kg, 4),
                    'scarichi_kg' => round((float) $row->scarichi_kg, 4),
                    'n_movimenti' => (int) $row->n_movimenti,
                    'saldo_kg' => round((float) $row->carichi_kg - (float) $row->scarichi_kg, 4),
                ];
            })
            ->sortBy('codice')
            ->values()
            ->all();

        $totals = [
            'carichi_kg' => array_sum(array_column($rows, 'carichi_kg')),
            'scarichi_kg' => array_sum(array_column($rows, 'scarichi_kg')),
            'saldo_kg' => array_sum(array_column($rows, 'saldo_kg')),
            'n_movimenti' => array_sum(array_column($rows, 'n_movimenti')),
        ];

        return ['rows' => $rows, 'totals' => $totals];
    }

    public function exportExcel(): BinaryFileResponse
    {
        $this->authorize('viewAny', RegistroMovimento::class);

        $bilancio = $this->bilancio();
        $dataDa = $this->data_da ?: 'inizio';
        $dataA = $this->data_a ?: 'oggi';
        $filename = "bilancio-cer_{$dataDa}_{$dataA}.xlsx";

        return Excel::download(new BilancioCerExport($bilancio), $filename);
    }

    public function exportCsv(): StreamedResponse
    {
        $this->authorize('viewAny', RegistroMovimento::class);

        $bilancio = $this->bilancio();
        $dataDa = $this->data_da ?: 'inizio';
        $dataA = $this->data_a ?: 'oggi';
        $filename = "bilancio-cer_{$dataDa}_{$dataA}.csv";

        return response()->streamDownload(function () use ($bilancio): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // BOM for Excel UTF-8
            fwrite($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['Codice CER', 'Descrizione', 'UM', 'Carichi (kg)', 'Scarichi (kg)', 'Saldo (kg)', 'N. movimenti'], ';');

            foreach ($bilancio['rows'] as $row) {
                fputcsv($handle, [
                    $row['codice'],
                    $row['descrizione'],
                    $row['um'],
                    number_format($row['carichi_kg'], 4, '.', ''),
                    number_format($row['scarichi_kg'], 4, '.', ''),
                    number_format($row['saldo_kg'], 4, '.', ''),
                    $row['n_movimenti'],
                ], ';');
            }

            $t = $bilancio['totals'];
            fputcsv($handle, [
                'TOTALE', '', '',
                number_format($t['carichi_kg'], 4, '.', ''),
                number_format($t['scarichi_kg'], 4, '.', ''),
                number_format($t['saldo_kg'], 4, '.', ''),
                $t['n_movimenti'],
            ], ';');

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.segreteria.report.bilancio-cer-index',
            [],
            'bilancio-cer',
            'Bilancio CER',
        );
    }
}
