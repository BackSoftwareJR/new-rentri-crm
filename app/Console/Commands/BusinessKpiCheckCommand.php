<?php

namespace App\Console\Commands;

use App\Domain\Dashboard\BusinessKpiAlertService;
use Illuminate\Console\Command;

class BusinessKpiCheckCommand extends Command
{
    protected $signature = 'kpi:business-check
                            {--notify : Invia email su breach soglia alert}
                            {--json : Output JSON per cron / monitoring}
                            {--period=last_7_days : Periodo last_7_days o last_30_days}';

    protected $description = 'Valuta KPI business vs soglie KPI_BUSINESS_* e notifica breach';

    public function handle(BusinessKpiAlertService $alerts): int
    {
        $period = (string) $this->option('period');
        $result = $alerts->check($period, (bool) $this->option('notify'));

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result['overall'] === 'fail' ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf(
            'KPI business check (%s) — esito: %s',
            $result['period_label'],
            strtoupper($result['overall']),
        ));

        foreach ($result['metrics'] as $key => $value) {
            $this->line(sprintf('  %s: %.2f', $key, $value));
        }

        if ($result['breaches'] === []) {
            $this->info('Nessun breach soglia KPI business.');
        } else {
            foreach ($result['breaches'] as $breach) {
                $line = sprintf('[%s] %s: %s', strtoupper($breach['status']), $breach['label'], $breach['message']);
                $breach['status'] === 'alert' ? $this->error($line) : $this->warn($line);
            }
        }

        if ($result['notified']) {
            $this->line('Notifica KPI business inviata.');
        }

        return $result['overall'] === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
