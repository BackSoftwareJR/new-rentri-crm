<?php

namespace App\Console\Commands;

use App\Domain\Audit\AuditExportLiveService;
use App\Jobs\AuditExportScheduledJob;
use Illuminate\Console\Command;

class AuditExportScheduledCommand extends Command
{
    protected $signature = 'audit:export-scheduled
                            {--dry-run : Simula export senza scrivere file}
                            {--queue : Accoda job Horizon invece di esecuzione sincrona}';

    protected $description = 'Export schedulato audit log su storage (CSV + checksum SHA-256)';

    public function handle(AuditExportLiveService $export): int
    {
        $dryRun = (bool) $this->option('dry-run');

        if ((bool) $this->option('queue')) {
            AuditExportScheduledJob::dispatch($dryRun, auth()->id());
            $this->info('Job audit:export-scheduled accodato.');

            return self::SUCCESS;
        }

        $run = $export->export(dryRun: $dryRun, triggeredBy: auth()->id());
        $purged = $export->purgeExpired();

        $this->info('Export audit'.($dryRun ? ' (dry-run)' : '').' completato.');
        $this->line('  Export ID: '.$run->export_id);
        $this->line('  Righe: '.$run->row_count);
        $this->line('  Checksum SHA-256: '.$run->checksum_sha256);
        $this->line('  Disk: '.$run->disk);
        if (! $dryRun) {
            $this->line('  Path: '.$run->path);
        }
        $this->line('  Retention purge: '.$purged.' file rimossi');

        return self::SUCCESS;
    }
}
