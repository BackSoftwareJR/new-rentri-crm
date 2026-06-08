<?php

namespace App\Console\Commands;

use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use Illuminate\Console\Command;

class SyncRentriCodificheCommand extends Command
{
    protected $signature = 'rentri:sync-codifiche';

    protected $description = 'Sincronizza codici CER dal catalogo RENTRI (sandbox stub o API live)';

    public function handle(RentriCodificheSyncInterface $sync): int
    {
        $this->info('Avvio sync codifiche CER RENTRI…');

        try {
            $result = $sync->sync();
        } catch (\Throwable $e) {
            $this->error('Sync fallita: '.$e->getMessage());

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Sync completata.');
        $this->line('  Nuovi: '.($result['created'] ?? 0));
        $this->line('  Aggiornati: '.($result['updated'] ?? 0));
        $this->line('  Disattivati: '.($result['deactivated'] ?? 0));
        $this->line('  Invariati: '.($result['skipped'] ?? 0));

        $this->printCodeList('Nuovi', $result['created_codes'] ?? []);
        $this->printCodeList('Aggiornati', $result['updated_codes'] ?? []);
        $this->printCodeList('Disattivati', $result['deactivated_codes'] ?? []);

        return self::SUCCESS;
    }

    /**
     * @param  list<string>  $codes
     */
    private function printCodeList(string $label, array $codes): void
    {
        if ($codes === []) {
            return;
        }

        $this->line("  {$label}: ".implode(', ', $codes));
    }
}
