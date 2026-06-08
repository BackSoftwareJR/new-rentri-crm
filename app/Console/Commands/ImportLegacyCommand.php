<?php

namespace App\Console\Commands;

use App\Domain\Legacy\LegacyImportService;
use Illuminate\Console\Command;

class ImportLegacyCommand extends Command
{
    protected $signature = 'rentri:import-legacy
                            {entity=anagrafiche : Entità da importare (anagrafiche|codici_cer|vfu|movimenti|ricambi)}
                            {--file= : Path CSV/JSON (default: database/fixtures/legacy/)}
                            {--dry-run : Simula import senza scrivere nel DB}
                            {--limit= : Numero massimo di righe da processare}
                            {--report : Riepilogo record legacy importati nel DB}';

    protected $description = 'Import stub da file fixture legacy (anagrafiche, CER, VFU, movimenti, ricambi)';

    public function handle(LegacyImportService $import): int
    {
        if ((bool) $this->option('report')) {
            return $this->printReport($import);
        }

        $entity = (string) $this->argument('entity');
        $dryRun = (bool) $this->option('dry-run');
        $limit = $this->option('limit') !== null ? max(1, (int) $this->option('limit')) : null;

        try {
            $file = $this->option('file') ?: $import->defaultFixturePath($entity);
            $result = $import->import($entity, $file, $dryRun, $limit);
        } catch (\Throwable $e) {
            $this->error('Import fallito: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->info(sprintf(
            'Import legacy %s%s da %s',
            $entity,
            $dryRun ? ' (dry-run)' : '',
            $file,
        ));

        $this->newLine();
        $this->info('Import completato.');
        $this->line('  Righe processate: '.$result['processed']);
        $this->line('  Importate: '.$result['imported']);
        $this->line('  Saltate (duplicate): '.$result['skipped']);

        foreach ($result['errors'] as $error) {
            $this->warn('  '.$error);
        }

        return self::SUCCESS;
    }

    private function printReport(LegacyImportService $import): int
    {
        $rows = collect($import->reportRows())->map(fn (array $row) => [
            $row['label'],
            (string) $row['count'],
            $row['status'] === 'imported' ? '✓ importato' : '— vuoto',
        ])->all();

        $total = $import->reportTotal();

        $this->newLine();
        $this->info('Report import legacy — database corrente');
        $this->line('  Comando: php artisan rentri:import-legacy --report');
        $this->line('  Generato: '.now()->format('d/m/Y H:i:s'));
        $this->newLine();
        $this->table(['Entità', 'Record', 'Stato'], $rows);
        $this->newLine();
        $this->info('Totale record legacy tracciati: '.$total);

        if ($total === 0) {
            $this->comment('Nessun record legacy rilevato. Esegui import per entità: anagrafiche, codici_cer, vfu, movimenti, ricambi.');
        } else {
            $this->comment('Verifica coerenza con fixture in database/fixtures/legacy/ e audit modulo legacy.');
        }

        return self::SUCCESS;
    }
}
