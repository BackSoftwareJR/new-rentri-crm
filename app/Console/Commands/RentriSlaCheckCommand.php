<?php

namespace App\Console\Commands;

use App\Domain\Rentri\RentriSlaAlertService;
use Illuminate\Console\Command;

class RentriSlaCheckCommand extends Command
{
    protected $signature = 'rentri:sla-check
                            {--notify : Invia notifiche hub/email su breach SLA e dead-letter nuovi}
                            {--json : Output JSON per cron / monitoring}
                            {--days=7 : Periodo valutazione in giorni (7 o 30)}';

    protected $description = 'Valuta SLA RENTRI (P95 latency, dead-letter rate) vs soglie RENTRI_SLA_*';

    public function handle(RentriSlaAlertService $alerts): int
    {
        $days = (int) $this->option('days');
        $result = $alerts->check($days, (bool) $this->option('notify'));

        if ($this->option('json')) {
            $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

            return $result['overall'] === 'fail' ? self::FAILURE : self::SUCCESS;
        }

        $this->info(sprintf(
            'SLA check (%d gg) — esito: %s',
            $result['period_days'],
            strtoupper($result['overall']),
        ));

        $metrics = $result['metrics'];
        $thresholds = $result['thresholds'];

        $this->line(sprintf(
            '  p95: %s s (soglia %d s)',
            $metrics['p95_seconds'] ?? '—',
            $thresholds['p95_latency_seconds'],
        ));
        $this->line(sprintf(
            '  dead-letter: %d (%.2f%%, soglia %.2f%%)',
            $metrics['dead_letter_count'],
            $metrics['dead_letter_rate'],
            $thresholds['dead_letter_rate_percent'],
        ));

        if ($result['breaches'] === []) {
            $this->info('Nessun breach SLA.');
        } else {
            foreach ($result['breaches'] as $breach) {
                $line = sprintf('[%s] %s: %s', strtoupper($breach['status']), $breach['label'], $breach['message']);
                $breach['status'] === 'fail' ? $this->error($line) : $this->warn($line);
            }
        }

        if ($result['notified']) {
            $this->line('Notifica SLA breach inviata.');
        }

        if (($result['new_dead_letters_notified'] ?? 0) > 0) {
            $this->line(sprintf('Notifiche dead-letter nuovi: %d', $result['new_dead_letters_notified']));
        }

        return $result['overall'] === 'fail' ? self::FAILURE : self::SUCCESS;
    }
}
