<?php

namespace App\Http\Livewire\Segreteria\Vfu;

use App\Domain\Vfu\VfuAccettazioneService;
use App\Support\UploadValidation;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Models\VfuRegistration;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\WithFileUploads;
use Symfony\Component\HttpFoundation\StreamedResponse;

class VfuImportCsv extends SegreteriaPage
{
    use AuthorizesRequests;
    use WithFileUploads;

    public bool $showModal = false;

    public $csvFile = null;

    /** @var list<array<string, string>> */
    public array $previewRows = [];

    /** @var array{imported: int, errors: list<array<string, mixed>>, total: int}|null */
    public ?array $importResult = null;

    public function mount(): void
    {
        $this->authorize('create', VfuRegistration::class);
    }

    public function openModal(): void
    {
        $this->authorize('create', VfuRegistration::class);
        $this->reset(['csvFile', 'previewRows', 'importResult']);
        $this->resetValidation();
        $this->showModal = true;
    }

    public function closeModal(): void
    {
        $this->showModal = false;
        $this->reset(['csvFile', 'previewRows', 'importResult']);
        $this->resetValidation();
    }

    public function updatedCsvFile(): void
    {
        $this->validate([
            'csvFile' => UploadValidation::csvRules(),
        ]);

        $this->importResult = null;
        $this->previewRows = array_slice($this->parseCsvFile(), 0, 5);
    }

    public function import(VfuAccettazioneService $service): void
    {
        $this->authorize('create', VfuRegistration::class);

        $this->validate([
            'csvFile' => UploadValidation::csvRules(),
        ]);

        $rows = $this->parseCsvFile();

        if ($rows === []) {
            $this->addError('csvFile', 'Il file CSV non contiene righe dati.');

            return;
        }

        $this->importResult = $service->accettaBatch($rows);

        if ($this->importResult['imported'] > 0) {
            $this->dispatch('vfu-imported');
        }
    }

    public function downloadTemplate(): StreamedResponse
    {
        $this->authorize('create', VfuRegistration::class);

        $headers = VfuAccettazioneService::csvImportHeaders();

        return response()->streamDownload(function () use ($headers): void {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            fputcsv($handle, $headers, ';');
            fputcsv($handle, [
                'AB123CD',
                'ZFA22300005555555',
                'FIAT',
                'Panda',
                '2015',
                'Bianco',
                '08/06/2026',
                'Mario Rossi',
                'RSSMRA80A01H501U',
                'mario.rossi@example.com',
            ], ';');

            fclose($handle);
        }, 'vfu-import-template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<array<string, string>>
     */
    private function parseCsvFile(): array
    {
        if ($this->csvFile === null) {
            return [];
        }

        $path = $this->csvFile->getRealPath();
        if ($path === false) {
            return [];
        }

        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $headerRow = fgetcsv($handle, 0, ';') ?: fgetcsv($handle) ?: [];
        $headerRow = array_map(fn ($h) => strtolower(trim((string) $h)), $headerRow);
        $expected = VfuAccettazioneService::csvImportHeaders();
        $normalizedExpected = array_map('strtolower', $expected);

        if ($headerRow !== $normalizedExpected) {
            $this->addError('csvFile', 'Intestazioni CSV non valide. Scaricare il template e riprovare.');

            return [];
        }

        $rows = [];

        while (($data = fgetcsv($handle, 0, ';')) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }

            $values = array_pad(array_map(fn ($v) => trim((string) $v), $data), count($expected), '');
            $row = array_combine($expected, $values);

            if ($row === false) {
                continue;
            }

            if (implode('', $row) === '') {
                continue;
            }

            $rows[] = $row;
        }

        fclose($handle);

        return $rows;
    }

    public function render(): View
    {
        return view('livewire.segreteria.vfu.vfu-import-csv');
    }
}
