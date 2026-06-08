<?php

namespace App\Console\Commands;

use App\Models\Fattura;
use App\Services\Push\WebPushService;
use App\Support\Logging\StructuredLogService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class FattureSegnalaScaduteCommand extends Command
{
    protected $signature = 'fatture:segna-scadute';

    protected $description = 'Segna come scadute le fatture emesse oltre la data di scadenza';

    public function handle(StructuredLogService $log, WebPushService $webPush): int
    {
        $today = Carbon::today()->toDateString();
        $yesterday = Carbon::yesterday()->toDateString();

        $fatture = Fattura::query()
            ->where('stato', 'emessa')
            ->whereNotNull('data_scadenza')
            ->where('data_scadenza', '<', $today)
            ->get();

        $count = $fatture->count();

        if ($count > 0) {
            Fattura::query()
                ->whereIn('id', $fatture->pluck('id'))
                ->update(['stato' => 'scaduta']);
        }

        foreach ($fatture as $fattura) {
            if ($fattura->data_scadenza?->toDateString() !== $yesterday) {
                continue;
            }

            try {
                $webPush->sendToRoles(
                    'segreteria',
                    'Fattura '.$fattura->numero_fattura.' scaduta ieri',
                    'Azione richiesta — verificare il pagamento.',
                    route('segreteria.fatture.show', $fattura),
                );
            } catch (\Throwable) {
                // Push failures must never break core workflow.
            }
        }

        $log->info('business', 'fatture.segnate_scadute', 'Fatture segnate come scadute', [
            'count' => $count,
            'date'  => $today,
        ]);

        $this->info("{$count} fatture segnate come scadute");

        return self::SUCCESS;
    }
}
