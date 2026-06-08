<?php

namespace App\Domain\Legacy;

use App\Domain\Audit\ActivityLogService;
use App\Models\LegacyImportSyncRun;
use App\Support\Demo\DemoContext;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class LegacyImportSyncService
{
    private const LOCK_KEY = 'legacy:sync-incremental';

    private const LOCK_SECONDS = 300;

    public function __construct(
        private LegacyImportService $import,
        private LegacyImportDiffReportService $diffReport,
    ) {}

    /**
     * @return array<string, array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}>
     */
    public function syncIncremental(bool $dryRun = false, ?int $triggeredBy = null): array
    {
        if (! $dryRun && ! $this->acquireLock()) {
            throw new \RuntimeException('Sync incrementale legacy già in esecuzione — riprova tra qualche minuto.');
        }

        $runId = (string) Str::uuid();
        $startedAt = now();
        $entityResults = [];

        try {
            foreach (LegacyImportService::SYNC_ENTITIES as $entity) {
                $path = $this->import->defaultFixturePath($entity);
                $entityResults[$entity] = $this->syncEntity($entity, $path, $dryRun);
            }

            $diffSummary = $this->diffReport->fromEntityResults($entityResults);
            $totals = $this->diffReport->totals($diffSummary);

            $run = LegacyImportSyncRun::create([
                'run_id'        => $runId,
                'status'        => 'completed',
                'dry_run'       => $dryRun,
                'entities'      => LegacyImportService::SYNC_ENTITIES,
                'diff_summary'  => $diffSummary,
                'total_new'     => $totals['new'],
                'total_updated' => $totals['updated'],
                'total_skipped' => $totals['skipped'],
                'total_errors'  => $totals['errors'],
                'triggered_by'  => $triggeredBy,
                'is_demo'       => DemoContext::isActive(),
                'started_at'    => $startedAt,
                'finished_at'   => now(),
            ]);

            app(ActivityLogService::class)->record(
                'legacy',
                $dryRun ? 'Sync incrementale legacy (dry-run)' : 'Sync incrementale legacy completato',
                subject: $run,
                properties: [
                    'run_id'        => $runId,
                    'dry_run'       => $dryRun,
                    'entities'      => LegacyImportService::SYNC_ENTITIES,
                    'total_new'     => $totals['new'],
                    'total_updated' => $totals['updated'],
                    'total_skipped' => $totals['skipped'],
                    'total_errors'  => $totals['errors'],
                ],
            );

            return $entityResults;
        } finally {
            if (! $dryRun) {
                $this->releaseLock();
            }
        }
    }

    /**
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}
     */
    public function syncEntity(string $entity, string $filePath, bool $dryRun = false): array
    {
        if (! in_array($entity, LegacyImportService::SYNC_ENTITIES, true)) {
            throw new \InvalidArgumentException('Entità non supportata per sync incrementale: '.$entity);
        }

        return match ($entity) {
            'anagrafiche' => $this->import->syncAnagrafiche($filePath, $dryRun),
            'codici_cer'  => $this->import->syncCodiciCer($filePath, $dryRun),
            'movimenti'   => $this->wrapMovimentiSync($this->import->import('movimenti', $filePath, $dryRun)),
        };
    }

    /**
     * @param  array{entity: string, dry_run: bool, processed: int, imported: int, skipped: int, errors: list<string>}  $result
     * @return array{entity: string, dry_run: bool, processed: int, imported: int, updated: int, skipped: int, errors: list<string>}
     */
    private function wrapMovimentiSync(array $result): array
    {
        $result['updated'] = 0;

        return $result;
    }

    private function acquireLock(): bool
    {
        return Cache::add(self::LOCK_KEY, true, self::LOCK_SECONDS);
    }

    private function releaseLock(): void
    {
        Cache::forget(self::LOCK_KEY);
    }
}
