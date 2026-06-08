<?php

namespace App\Console\Commands;

use App\Domain\Magazzino\SerbatoioAlertNotificationService;
use App\Domain\Magazzino\SerbatoioAlertService;
use Illuminate\Console\Command;

class MagazzinoControllaGiacenzeCommand extends Command
{
    protected $signature = 'magazzino:controlla-giacenze
                            {--notify : Invia notifiche in-app ed email per serbatoi sotto soglia minima}';

    protected $description = 'Controlla giacenze serbatoi rispetto alla soglia minima configurata';

    public function handle(
        SerbatoioAlertService $alerts,
        SerbatoioAlertNotificationService $notifications,
    ): int {
        $rows = $alerts->giacenzeSottoMinimo();

        if ($rows->isEmpty()) {
            $this->info('Nessun serbatoio sotto soglia minima.');

            return self::SUCCESS;
        }

        $this->warn(sprintf('%d serbatoio/i sotto soglia minima:', $rows->count()));

        foreach ($rows as $row) {
            $this->line(sprintf(
                '  %s — %.2f kg (soglia min. %.2f kg)',
                $row['codice'],
                $row['quantita_attuale_kg'],
                $row['soglia_minima_kg'],
            ));

            if ($this->option('notify')) {
                $notifications->notifyMinimumStock($row);
            }
        }

        if ($this->option('notify')) {
            $this->info('Notifiche inviate.');
        }

        return self::SUCCESS;
    }
}
