<?php

namespace App\Console\Commands;

use App\Domain\Infrastructure\ApplicationHealthService;
use Illuminate\Console\Command;

class HealthCheckCommand extends Command
{
    protected $signature = 'app:health-check
                            {--json : Output JSON only (no colored lines)}';

    protected $description = 'Health check operativo: DB, Redis, queue, Horizon, storage, cert RENTRI, scheduler';

    public function handle(ApplicationHealthService $health): int
    {
        $report = $health->diagnose();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Application health check — '.$report['status']);
        $this->newLine();

        foreach ($report['checks'] as $name => $check) {
            $icon = match ($check['status']) {
                'ok'   => '<fg=green>OK</>',
                'warn' => '<fg=yellow>WARN</>',
                default => '<fg=red>FAIL</>',
            };

            $this->line(sprintf('[%s] %s — %s', $icon, $name, $check['message']));
        }

        $this->newLine();
        $this->line('Checked at: '.$report['checked_at']);

        return $report['status'] === 'healthy' ? self::SUCCESS : self::FAILURE;
    }
}
