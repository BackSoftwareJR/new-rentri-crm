<?php

namespace App\Console\Commands;

use App\Domain\Gdpr\GdprService;
use App\Support\Logging\StructuredLogService;
use Illuminate\Console\Command;

class GdprProcessDeletionsCommand extends Command
{
    protected $signature = 'gdpr:process-deletions';

    protected $description = 'Soft-delete accounts GDPR oltre il periodo di grazia di 30 giorni';

    public function handle(GdprService $gdpr, StructuredLogService $log): int
    {
        $count = $gdpr->processScheduledDeletions();

        $log->info('security', 'gdpr.deletions_processed', 'Account GDPR eliminati', [
            'count' => $count,
        ]);

        $this->info("{$count} account eliminati (soft-delete).");

        return self::SUCCESS;
    }
}
