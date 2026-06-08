<?php

namespace App\Console\Commands;

use App\Domain\Rentri\RentriProductionSwitchService;
use Illuminate\Console\Command;

class RentriProductionSwitchCheckCommand extends Command
{
    protected $signature = 'rentri:production-switch-check
                            {--dry-run : Report checklist senza modifiche (default)}';

    protected $description = 'Verifica readiness switch RENTRI produzione MASE (checklist unificata + preflight)';

    public function handle(RentriProductionSwitchService $switch): int
    {
        $this->info('RENTRI production switch — dry-run report');
        $this->newLine();

        $report = $switch->dryRunReport();
        $summary = $report['summary'];

        $this->line(sprintf(
            'Ambiente: RENTRI_ENV=%s · Runtime: %s · Produzione attiva: %s',
            $summary['rentri_env'],
            $summary['api_mode'],
            $report['production_active'] ? 'sì' : 'no',
        ));
        $this->line(sprintf(
            'Checklist obbligatoria: %d/%d OK',
            $summary['ok'],
            $summary['total'],
        ));

        if ($summary['optional_pending'] > 0) {
            $this->warn(sprintf('Voci opzionali pendenti: %d (es. WAF block)', $summary['optional_pending']));
        }

        $this->newLine();
        $this->info('Checklist unificata:');

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

        $this->newLine();
        $this->info('Preflight (rentri:preflight):');

        foreach ($report['preflight']['checks'] as $check) {
            $line = sprintf('[%s] %s: %s', strtoupper($check['status']), $check['name'], $check['message']);

            match ($check['status']) {
                'fail'  => $this->error($line),
                'warn'  => $this->warn($line),
                default => $this->line($line),
            };
        }

        $this->newLine();
        $this->line('Runbook: '.$switch->runbookRelativePath());

        if ($report['passed']) {
            $this->info('Switch produzione: PRONTO (dry-run).');

            return self::SUCCESS;
        }

        $this->error('Switch produzione: NON PRONTO — correggere voci FAIL.');

        return self::FAILURE;
    }
}
