<?php

namespace App\Console\Commands;

use App\Domain\Trasporti\TrasportoGpsProductionSwitchService;
use Illuminate\Console\Command;

class TrasportoGpsSwitchCheckCommand extends Command
{
    protected $signature = 'trasporto:gps-switch-check
                            {--dry-run : Report checklist senza modifiche (default)}
                            {--probe : Esegue probe HTTP verso provider live}
                            {--json : Output JSON}';

    protected $description = 'Verifica readiness switch GPS provider live (stub → produzione)';

    public function handle(TrasportoGpsProductionSwitchService $switch): int
    {
        $report = $switch->dryRunReport((bool) $this->option('probe'));

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['passed'] ? self::SUCCESS : self::FAILURE;
        }

        $summary = $report['summary'];

        $this->info('GPS provider switch — dry-run report');
        $this->newLine();
        $this->line(sprintf(
            'Modalità: %s · Live attivo: %s · Preset field map: %s',
            $summary['mode_label'],
            $report['live_active'] ? 'sì' : 'no',
            $summary['field_map_preset'] ?? 'custom',
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
        $this->info('Checklist:');

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

        if ($report['probe'] !== null) {
            $this->newLine();
            $this->info('Probe provider:');
            $probe = $report['probe'];
            $probe['ok'] ? $this->line($probe['message']) : $this->error($probe['message']);
        }

        $this->newLine();
        $this->line('Runbook: '.$switch->runbookRelativePath());
        $this->line('Preset field map: flat_default · nested_fleet (vedi tests/fixtures/gps/position-response.json)');

        if ($report['passed']) {
            $this->info('Switch GPS live: PRONTO (dry-run).');

            return self::SUCCESS;
        }

        $this->error('Switch GPS live: NON PRONTO — correggere voci FAIL.');

        return self::FAILURE;
    }
}
