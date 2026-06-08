<?php

namespace App\Console\Commands;

use App\Domain\Infrastructure\HaFailoverDrillService;
use Illuminate\Console\Command;

class HaFailoverDrillCommand extends Command
{
    protected $signature = 'ha:failover-drill
                            {--dry-run : Report checklist esercitazione (default)}
                            {--probe : Probe GET /up su nodi primario/secondario}
                            {--json : Output JSON}';

    protected $description = 'Esercitazione failover multi-istanza — health, switch traffic, recovery';

    public function handle(HaFailoverDrillService $drill): int
    {
        $report = $drill->dryRunReport((bool) $this->option('probe'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['passed'] ? self::SUCCESS : self::FAILURE;
        }

        $summary = $report['summary'];

        $this->info('HA failover drill — dry-run report');
        $this->newLine();
        $this->line(sprintf(
            'Drill pronto: %s · Preflight HA: %s · Ultimo drill: %s',
            $summary['ready'] ? 'sì' : 'no',
            $summary['ha_preflight_ready'] ? 'OK' : 'incompleto',
            $summary['last_drill'] ?? '—',
        ));
        $this->line(sprintf(
            'Checklist obbligatoria: %d/%d OK',
            $summary['ok'],
            $summary['total'],
        ));

        if ($summary['optional_pending'] > 0) {
            $this->warn(sprintf('Voci opzionali pendenti: %d', $summary['optional_pending']));
        }

        $this->newLine();
        $this->info('Checklist readiness:');

        foreach ($report['checklist'] as $item) {
            $tag = $item['ok'] ? 'OK' : ($item['optional'] ? 'OPT' : 'FAIL');
            $optional = $item['optional'] ? ' [opzionale]' : '';
            $line = sprintf('[%s] %s%s', $tag, $item['label'], $optional);

            if ($item['ok']) {
                $this->line($line);
            } elseif ($item['optional']) {
                $this->warn($line);
            } else {
                $this->error($line);
            }

            if (! $item['ok'] && $item['hint']) {
                $this->line('      → '.$item['hint']);
            }
        }

        foreach (['health' => 'Fase 1 — Health', 'traffic_switch' => 'Fase 2 — Switch traffic', 'recovery' => 'Fase 3 — Recovery'] as $phase => $title) {
            $this->newLine();
            $this->info($title);

            foreach ($report['phases'][$phase] as $step) {
                if (isset($step['action'])) {
                    $this->line(sprintf('  %d. %s — %s', $step['step'], $step['action'], $step['detail']));
                } else {
                    $status = $step['ok'] ? 'OK' : 'PENDING';
                    $this->line(sprintf('  [%s] %s', $status, $step['label']));
                }
            }
        }

        if ($report['probe'] !== null) {
            $this->newLine();
            $this->info('Probe nodi:');
            foreach (['primary' => 'Primario', 'secondary' => 'Secondario'] as $key => $label) {
                $node = $report['probe'][$key];
                $node['ok'] ? $this->line($label.': '.$node['message']) : $this->error($label.': '.$node['message']);
            }
        }

        $this->newLine();
        $this->line('Runbook: '.$drill->runbookRelativePath());
        $this->line('UI: /admin/ha-status');

        if ($report['passed']) {
            $this->info('Failover drill: PRONTO (dry-run).');

            return self::SUCCESS;
        }

        $this->error('Failover drill: NON PRONTO — correggere voci FAIL.');

        return self::FAILURE;
    }
}
