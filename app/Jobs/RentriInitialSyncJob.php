<?php

namespace App\Jobs;

use App\Domain\Magazzino\MagazzinoService;
use App\Enums\NotificationEvent;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Services\Rentri\Contracts\RentriCodificheSyncInterface;
use App\Services\Rentri\Contracts\RentriFirBlocchiSyncInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class RentriInitialSyncJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 3;

    public int $backoff = 60;

    public function __construct()
    {
        $this->onQueue('rentri');
    }

    public function handle(
        RentriCodificheSyncInterface $codificheSync,
        RentriFirBlocchiSyncInterface $firBlocchiSync,
        MagazzinoService $magazzino,
    ): void {
        Log::info('RentriInitialSyncJob: avvio sincronizzazione iniziale RENTRI');

        $codificheResult = $codificheSync->sync();
        $serbatoi = $magazzino->ensureSerbatoi();
        $firResult = $firBlocchiSync->sync();

        Log::info('RentriInitialSyncJob: completato', [
            'codifiche_created'     => $codificheResult['created'] ?? 0,
            'codifiche_updated'     => $codificheResult['updated'] ?? 0,
            'codifiche_deactivated' => $codificheResult['deactivated'] ?? 0,
            'serbatoi_created'      => $serbatoi,
            'fir_blocchi_created'   => $firResult['created'] ?? 0,
            'fir_blocchi_updated'   => $firResult['updated'] ?? 0,
        ]);
    }

    public function failed(\Throwable $exception): void
    {
        Log::error('RentriInitialSyncJob: fallito', [
            'error' => $exception->getMessage(),
        ]);

        User::role('admin')
            ->get()
            ->each(function (User $admin) use ($exception) {
                $admin->notify(new AppNotification(
                    event: NotificationEvent::RentriInitialSyncFailed,
                    title: 'Sincronizzazione RENTRI fallita',
                    body: 'La sincronizzazione iniziale CER/FIR è fallita: '.$exception->getMessage(),
                    url: '/impostazioni/rentri',
                ));
            });
    }
}
