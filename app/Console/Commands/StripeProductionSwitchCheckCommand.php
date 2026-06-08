<?php

namespace App\Console\Commands;

use App\Domain\Ecommerce\StripeProductionSwitchService;
use Illuminate\Console\Command;

class StripeProductionSwitchCheckCommand extends Command
{
    protected $signature = 'stripe:production-switch-check
                            {--dry-run : Report checklist senza modifiche (default)}
                            {--json : Output JSON}';

    protected $description = 'Verifica readiness switch Stripe sandbox → produzione e-commerce';

    public function handle(StripeProductionSwitchService $switch): int
    {
        $report = $switch->dryRunReport();

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $report['passed'] ? self::SUCCESS : self::FAILURE;
        }

        $summary = $report['summary'];

        $this->info('Stripe production switch — dry-run report');
        $this->newLine();
        $this->line(sprintf(
            'Modalità: %s · Produzione attiva: %s · Dashboard: %s',
            $summary['mode_label'],
            $report['production_active'] ? 'sì' : 'no',
            $summary['dashboard_url'],
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

        $this->newLine();
        $this->line('Runbook: '.$switch->runbookRelativePath());
        $this->line('Reconciliation: StripeReconciliationReportService · export CSV da hub e-commerce');

        if ($report['passed']) {
            $this->info('Switch Stripe produzione: PRONTO (dry-run).');

            return self::SUCCESS;
        }

        $this->error('Switch Stripe produzione: NON PRONTO — correggere voci FAIL.');

        return self::FAILURE;
    }
}
