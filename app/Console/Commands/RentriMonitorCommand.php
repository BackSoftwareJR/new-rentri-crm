<?php

namespace App\Console\Commands;

use App\Domain\Deploy\Cycle3MonitoringService;
use Illuminate\Console\Command;

class RentriMonitorCommand extends Command
{
    protected $signature = 'rentri:monitor {--json : Output JSON per integrazione alerting}';

    protected $description = 'Snapshot monitoraggio ciclo 3: health /up, KPI RENTRI dead-letter/retry, alert configurazione demo/prod';

    public function handle(Cycle3MonitoringService $monitoring): int
    {
        $snapshot = $monitoring->snapshot();

        if ($this->option('json')) {
            $this->line(json_encode($snapshot, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return empty($snapshot['alerts']) ? self::SUCCESS : self::FAILURE;
        }

        $this->info('Monitoraggio RENTRI CRM (ciclo 3)…');
        $this->newLine();

        $health = $snapshot['framework_health'];
        $line = sprintf('[%s] framework_health: %s', strtoupper($health['status']), $health['message']);
        $health['status'] === 'ok' ? $this->line($line) : $this->error($line);

        $rentri = $snapshot['rentri'];
        $this->line(sprintf(
            '[INFO] rentri_api: totale=%d errori=%d dead_letter=%d retry_pianificati=%d',
            $rentri['totale'],
            $rentri['errori'],
            $rentri['dead_letter'],
            $rentri['retry_pianificati'],
        ));

        $this->line(sprintf(
            '[INFO] deploy: demo_mode=%s app_env=%s',
            $snapshot['demo_mode'] ? 'true' : 'false',
            $snapshot['app_env'],
        ));

        $this->newLine();

        if ($snapshot['alerts'] === []) {
            $this->info('Nessun alert attivo.');

            return self::SUCCESS;
        }

        foreach ($snapshot['alerts'] as $alert) {
            $formatted = sprintf('[%s] %s: %s', strtoupper($alert['level']), $alert['code'], $alert['message']);
            $alert['level'] === 'critical' ? $this->error($formatted) : $this->warn($formatted);
        }

        $hasCritical = collect($snapshot['alerts'])->contains(fn (array $a) => $a['level'] === 'critical');

        return $hasCritical ? self::FAILURE : self::SUCCESS;
    }
}
