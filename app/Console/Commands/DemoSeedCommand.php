<?php

namespace App\Console\Commands;

use App\Domain\Demo\DemoSeedService;
use App\Support\Demo\DemoContext;
use Illuminate\Console\Command;

class DemoSeedCommand extends Command
{
    protected $signature = 'rentri:demo-seed
                            {--fresh : Esegue rentri:demo-reset prima del seed}
                            {--force : Esegue anche se APP_DEMO_MODE=false}';

    protected $description = 'Popola fixture demo (is_demo=true): settings, blocco FIR, trasporto, movimento registro';

    public function handle(DemoSeedService $seed): int
    {
        if (! DemoContext::isActive() && ! $this->option('force')) {
            $this->error('Modalità demo non attiva — attiva la palestra operativa in sidebar o usa --force.');

            return self::FAILURE;
        }

        if ($this->option('fresh')) {
            $this->warn('Reset dati demo prima del seed…');
            $this->call('rentri:demo-reset', $this->option('force') ? ['--force' => true] : []);
        }

        $result = $seed->seed();

        if ($result['skipped'] ?? false) {
            $this->info('Seed demo già presente — nessuna modifica (usa --fresh per rigenerare).');
            $this->line('  Trasporto demo: '.($result['trasporto'] ?? '—'));
            $this->line('  Movimento demo: '.($result['movimento'] ?? '—'));

            return self::SUCCESS;
        }

        $this->info('Seed demo completato.');
        foreach (['rentri_settings', 'fir_blocco', 'trasporto', 'registro_movimento'] as $key) {
            if (isset($result[$key])) {
                $this->line(sprintf('  %s: #%s', $key, $result[$key]));
            }
        }

        return self::SUCCESS;
    }
}
