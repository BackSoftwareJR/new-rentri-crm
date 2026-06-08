<?php

namespace App\Console\Commands;

use App\Domain\Deploy\DemoPreflightService;
use App\Domain\Deploy\PreflightService;
use Illuminate\Console\Command;

class PreflightCommand extends Command
{
    protected $signature = 'rentri:preflight
                            {--demo : Preflight istanza demo/staging (APP_DEMO_MODE=true)}
                            {--require-seed : Con --demo: fallisce se rentri:demo-seed non eseguito}';

    protected $description = 'Verifica pre-deploy: env, DB, manifest Vite, certificati RENTRI mTLS + firma xFIR (no HTTP)';

    public function handle(PreflightService $preflight, DemoPreflightService $demoPreflight): int
    {
        if ($this->option('demo')) {
            $this->info('Preflight deploy demo RENTRI CRM…');
        } else {
            $this->info('Preflight deploy RENTRI CRM…');
        }
        $this->newLine();

        $result = $this->option('demo')
            ? $demoPreflight->run(requireSeed: (bool) $this->option('require-seed'))
            : $preflight->run();

        foreach ($result['checks'] as $check) {
            $line = sprintf('[%s] %s: %s', strtoupper($check['status']), $check['name'], $check['message']);

            match ($check['status']) {
                'fail'  => $this->error($line),
                'warn'  => $this->warn($line),
                default => $this->line($line),
            };

            if ($check['status'] === 'fail') {
                app(\App\Support\Logging\StructuredLogService::class)->error(
                    'security',
                    'deploy_preflight_fail',
                    'Preflight deploy fallito: '.$check['name'],
                    [
                        'outcome' => 'failure',
                        'context' => [
                            'check'   => $check['name'],
                            'message' => $check['message'],
                            'demo'    => (bool) $this->option('demo'),
                        ],
                    ],
                );
            }
        }

        $this->newLine();

        if ($result['passed']) {
            $this->info('Preflight completato — nessun errore bloccante.');

            return self::SUCCESS;
        }

        $this->error('Preflight fallito — correggere i check in stato FAIL.');

        return self::FAILURE;
    }
}
