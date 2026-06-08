<?php

namespace App\Jobs;

use App\Domain\Audit\AuditExportLiveService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class AuditExportScheduledJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 3600;

    public function __construct(
        public bool $dryRun = false,
        public ?int $triggeredBy = null,
    ) {
        $this->onQueue('exports');
    }

    public function uniqueId(): string
    {
        return 'audit-export-scheduled';
    }

    public function handle(AuditExportLiveService $export): void
    {
        $export->export(dryRun: $this->dryRun, triggeredBy: $this->triggeredBy);
        $export->purgeExpired();
    }
}
