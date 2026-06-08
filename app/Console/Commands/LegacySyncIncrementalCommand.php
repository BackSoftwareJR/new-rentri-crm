<?php

namespace App\Console\Commands;

use App\Domain\Legacy\LegacyImportDiffReportService;
use App\Domain\Legacy\LegacyImportService;
use App\Domain\Legacy\LegacyImportSyncService;
use App\Jobs\LegacyIncrementalSyncJob;
use Illuminate\Console\Command;

class LegacySyncIncrementalCommand extends Command
{
    protected $signature = 'legacy:sync-incremental
                            {--dry-run : Simula sync senza scrivere nel DB}
                            {--queue : Accoda job Horizon invece di esecuzione sincrona}';

    protected $description = 'Sync incrementale legacy da fixture (anagrafiche, CER, movimenti)';

    public function handle(
        LegacyImportSyncService $sync,
        LegacyImportDiffReportService $diffReport,
    ): int {
        $dryRun = (bool) $this->option('dry-run');

        if ((bool) $this->option('queue')) {
            LegacyIncrementalSyncJob::dispatch($dryRun, auth()->id());

            $this->info('Job legacy:sync-incremental accodato.');

            return self::SUCCESS;
        }

        try {
            $results = $sync->syncIncremental($dryRun, auth()->id());
        } catch (\Throwable $e) {
            $this->error('Sync fallito: '.$e->getMessage());

            return self::FAILURE;
        }

        $summary = $diffReport->fromEntityResults($results);
        $totals = $diffReport->totals($summary);

        $this->info('Sync incrementale legacy'.($dryRun ? ' (dry-run)' : '').' completato.');
        $this->newLine();

        $rows = collect($summary)->map(fn (array $row, string $entity) => [
            LegacyImportService::entityLabels()[$entity] ?? $entity,
            (string) $row['new'],
            (string) $row['updated'],
            (string) $row['skipped'],
            (string) $row['errors'],
        ])->values()->all();

        $this->table(['Entità', 'Nuovi', 'Aggiornati', 'Skipped', 'Errori'], $rows);
        $this->newLine();
        $this->info(sprintf(
            'Totali: %d nuovi, %d aggiornati, %d skipped, %d errori',
            $totals['new'],
            $totals['updated'],
            $totals['skipped'],
            $totals['errors'],
        ));

        $last = $diffReport->lastRun();
        if ($last !== null) {
            $this->comment('Run ID: '.$last->run_id);
        }

        return self::SUCCESS;
    }
}
