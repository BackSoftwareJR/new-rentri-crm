<?php

namespace App\Domain\Audit;

use App\Models\AuditExportRun;
use App\Support\Demo\DemoContext;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AuditExportLiveService
{
    public function __construct(
        private ActivityLogService $activityLog,
    ) {}

    public function diskName(): string
    {
        return (string) config('audit.export.disk', 'audit_exports');
    }

    public function retentionDays(): int
    {
        return max(1, (int) config('audit.export.retention_days', 90));
    }

    /**
     * @param  array{data_da?: string|null, data_a?: string|null}  $filters
     */
    public function export(array $filters = [], bool $dryRun = false, ?int $triggeredBy = null): AuditExportRun
    {
        $dataDa = $filters['data_da'] ?? now()->subDays(7)->toDateString();
        $dataA = $filters['data_a'] ?? now()->toDateString();

        $exportFilters = array_filter([
            'data_da' => $dataDa,
            'data_a'  => $dataA,
        ]);

        $csv = $this->buildCsv($exportFilters);
        $checksum = hash('sha256', $csv);
        $rowCount = max(0, substr_count($csv, "\n") - 1);
        $exportId = (string) Str::uuid();
        $path = sprintf('audit-exports/%s/%s.csv', now()->format('Y-m-d'), $exportId);
        $disk = $this->diskName();

        if (! $dryRun) {
            Storage::disk($disk)->put($path, $csv);
        }

        $run = AuditExportRun::create([
            'export_id'       => $exportId,
            'disk'            => $disk,
            'path'            => $path,
            'checksum_sha256' => $checksum,
            'row_count'       => $rowCount,
            'file_size'       => strlen($csv),
            'status'          => $dryRun ? 'dry_run' : 'completed',
            'period_from'     => $dataDa,
            'period_to'       => $dataA,
            'dry_run'         => $dryRun,
            'triggered_by'    => $triggeredBy,
            'is_demo'         => DemoContext::isActive(),
            'expires_at'      => now()->addDays($this->retentionDays()),
        ]);

        if (! $dryRun) {
            $this->activityLog->record(
                'audit',
                'Export audit log CSV caricato su storage',
                subject: $run,
                properties: [
                    'export_id'       => $exportId,
                    'disk'            => $disk,
                    'path'            => $path,
                    'checksum_sha256' => $checksum,
                    'row_count'       => $rowCount,
                    'period_from'     => $dataDa,
                    'period_to'       => $dataA,
                ],
                userId: $triggeredBy,
            );
        }

        return $run;
    }

    public function purgeExpired(): int
    {
        $purged = 0;
        $disk = Storage::disk($this->diskName());

        AuditExportRun::query()
            ->where('dry_run', false)
            ->where(function ($query) {
                $query->whereNotNull('expires_at')
                    ->where('expires_at', '<', now());
            })
            ->orderBy('id')
            ->each(function (AuditExportRun $run) use ($disk, &$purged): void {
                if ($disk->exists($run->path)) {
                    $disk->delete($run->path);
                }

                $run->update(['status' => 'purged']);
                $purged++;
            });

        if ($purged > 0) {
            $this->activityLog->record(
                'audit',
                'Retention export audit: '.$purged.' file rimossi',
                properties: ['purged' => $purged],
            );
        }

        return $purged;
    }

    /**
     * @return \Illuminate\Support\Collection<int, AuditExportRun>
     */
    public function recentRuns(int $limit = 10): \Illuminate\Support\Collection
    {
        return AuditExportRun::query()
            ->where('dry_run', false)
            ->latest()
            ->limit(max(1, min(20, $limit)))
            ->get();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function buildCsv(array $filters): string
    {
        $handle = fopen('php://temp', 'r+');
        if ($handle === false) {
            throw new \RuntimeException('Impossibile creare buffer CSV.');
        }

        fputcsv($handle, $this->activityLog->csvHeader(), ';');

        $this->activityLog->exportQuery($filters)
            ->with('causer:id,name')
            ->chunkById(500, function ($activities) use ($handle): void {
                foreach ($activities as $activity) {
                    fputcsv($handle, $this->activityLog->csvRowFor($activity), ';');
                }
            });

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}
