<?php

namespace App\Jobs;

use App\Domain\Legacy\LegacyImportSyncService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class LegacyIncrementalSyncJob implements ShouldBeUnique, ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $uniqueFor = 300;

    public function __construct(
        public bool $dryRun = false,
        public ?int $triggeredBy = null,
    ) {}

    public function uniqueId(): string
    {
        return 'legacy-sync-incremental';
    }

    public function handle(LegacyImportSyncService $sync): void
    {
        $sync->syncIncremental($this->dryRun, $this->triggeredBy);
    }
}
