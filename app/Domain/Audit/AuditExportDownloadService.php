<?php

namespace App\Domain\Audit;

use App\Models\AuditExportRun;
use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\URL;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditExportDownloadService
{
    public function __construct(
        private ActivityLogService $activityLog,
    ) {}

    public function presignedTtlMinutes(): int
    {
        return max(5, (int) config('audit.export.presigned_ttl_minutes', 1440));
    }

    public function createDownloadUrl(AuditExportRun $run, User $user): string
    {
        if ($run->dry_run || $run->status === 'purged') {
            throw new \InvalidArgumentException('Export non disponibile per il download.');
        }

        if ($run->isExpired()) {
            throw new \InvalidArgumentException('Export scaduto — esegui un nuovo export schedulato.');
        }

        if (! Storage::disk($run->disk)->exists($run->path)) {
            throw new \InvalidArgumentException('File export non trovato su storage.');
        }

        $expiresAt = now()->addMinutes($this->presignedTtlMinutes());

        $this->activityLog->record(
            'audit',
            'Download export audit richiesto',
            subject: $run,
            properties: [
                'export_id'       => $run->export_id,
                'checksum_sha256' => $run->checksum_sha256,
                'requested_by'    => $user->id,
                'expires_at'      => $expiresAt->toIso8601String(),
            ],
            userId: $user->id,
        );

        $disk = Storage::disk($run->disk);

        if ($this->diskSupportsTemporaryUrls($run->disk)) {
            return $disk->temporaryUrl($run->path, $expiresAt, [
                'ResponseContentDisposition' => 'attachment; filename="'.$this->filename($run).'"',
            ]);
        }

        return URL::temporarySignedRoute(
            'admin.audit.export.download',
            $expiresAt,
            ['run' => $run->id],
        );
    }

    public function streamDownload(AuditExportRun $run): StreamedResponse
    {
        if ($run->dry_run || $run->status === 'purged' || $run->isExpired()) {
            abort(404, 'Export non disponibile.');
        }

        $disk = Storage::disk($run->disk);

        if (! $disk->exists($run->path)) {
            abort(404, 'File export non trovato.');
        }

        return $disk->download($run->path, $this->filename($run), [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function filename(AuditExportRun $run): string
    {
        return sprintf(
            'audit-export-%s-%s.csv',
            $run->period_from?->format('Ymd') ?? 'start',
            $run->export_id,
        );
    }

    private function diskSupportsTemporaryUrls(string $diskName): bool
    {
        $driver = config('filesystems.disks.'.$diskName.'.driver');

        return in_array($driver, ['s3'], true);
    }
}
