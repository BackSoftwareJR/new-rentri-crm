<?php

namespace App\Console\Commands;

use App\Domain\Logging\ApplicationLogQueryService;
use Illuminate\Console\Command;

class LogsPurgeCommand extends Command
{
    protected $signature = 'logs:purge {--days= : Giorni di retention (default da APP_LOG_RETENTION_DAYS)}';

    protected $description = 'Elimina application_logs più vecchi della retention configurata';

    public function handle(ApplicationLogQueryService $logs): int
    {
        $days = $this->option('days') !== null
            ? max(1, (int) $this->option('days'))
            : (int) config('application_log.retention_days', 90);

        $deleted = $logs->purgeOlderThan($days);

        $this->info(sprintf('Eliminati %d record più vecchi di %d giorni.', $deleted, $days));

        return self::SUCCESS;
    }
}
