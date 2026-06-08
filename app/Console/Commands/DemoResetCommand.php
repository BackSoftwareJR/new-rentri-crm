<?php

namespace App\Console\Commands;

use App\Domain\Demo\DemoResetService;
use App\Support\Demo\DemoContext;
use Illuminate\Console\Command;

class DemoResetCommand extends Command
{
    protected $signature = 'rentri:demo-reset {--force : Esegue anche se APP_DEMO_MODE=false}';

    protected $description = 'Elimina solo i record CRM con is_demo=true (dati piattaforma demo)';

    public function handle(DemoResetService $reset): int
    {
        if (! DemoContext::isActive() && ! $this->option('force')) {
            $this->error('APP_DEMO_MODE=false — usa --force solo se intendi cancellare i record demo in produzione.');

            return self::FAILURE;
        }

        $this->warn('Reset dati demo (is_demo=true)…');

        $counts = $reset->resetDemoData();
        $total = array_sum($counts);

        foreach ($counts as $table => $count) {
            $this->line(sprintf('  %s: %d', $table, $count));
        }

        $this->info("Reset completato — {$total} record demo eliminati.");

        return self::SUCCESS;
    }
}
