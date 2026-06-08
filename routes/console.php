<?php

use App\Domain\Infrastructure\ApplicationHealthService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::call(function (): void {
    app(ApplicationHealthService::class)->recordSchedulerHeartbeat();
})->everyMinute()
    ->name('health-scheduler-heartbeat')
    ->description('Heartbeat cron scheduler per app:health-check');

Schedule::command('gdpr:process-deletions')
    ->dailyAt('02:00')
    ->timezone('Europe/Rome')
    ->description('Soft-delete account GDPR oltre periodo di grazia 30 giorni');

Schedule::command('audit:export-scheduled')
    ->weeklyOn(1, '02:00')
    ->timezone('Europe/Rome')
    ->description('Export settimanale audit log CSV su storage');

Schedule::command('legacy:sync-incremental')
    ->weeklyOn(1, '03:00')
    ->timezone('Europe/Rome')
    ->description('Sync incrementale legacy da fixture');

Schedule::command('rentri:sla-check --notify')
    ->hourly()
    ->timezone('Europe/Rome')
    ->description('Valutazione SLA RENTRI e notifica breach/dead-letter');

Schedule::command('kpi:business-check --notify')
    ->dailyAt('07:30')
    ->timezone('Europe/Rome')
    ->description('Valutazione KPI business e notifica soglie alert');

Schedule::command('logs:purge')
    ->weeklyOn(0, '04:00')
    ->timezone('Europe/Rome')
    ->description('Purge application_logs oltre retention configurata');

Schedule::command('fatture:segna-scadute')
    ->dailyAt('00:05')
    ->timezone('Europe/Rome')
    ->description('Segna come scadute le fatture emesse oltre la data di scadenza');

Schedule::command('magazzino:controlla-giacenze --notify')
    ->everySixHours()
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->description('Controllo giacenze serbatoi vs soglia minima e notifica segreteria');

Schedule::command('rentri:trasmetti-registro --notify')
    ->quarterlyOn(1, '06:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->description('Trasmissione trimestrale registro RENTRI');

// rentri:go-live — Orchestrazione manuale pre-go-live (NON schedulato).
// Eseguire: php artisan rentri:go-live [--dry-run] [--force] [--notify]

// Sincronizzazione settimanale delle codifiche RENTRI (CER, operazioni, unità misura).
// Aggiorna le lookup table locali dai registri MASE per garantire dati aggiornati in dichiarazioni
// e validazioni (FIR, xFIR, MUD). Eseguito la domenica alle 05:00 per non sovrapporre altri job.
Schedule::command('rentri:sync-codifiche')
    ->weeklyOn(0, '05:00')
    ->timezone('Europe/Rome')
    ->withoutOverlapping()
    ->runInBackground()
    ->description('Sincronizzazione settimanale codifiche RENTRI (CER, operazioni, unità misura)')
    ->onFailure(function () {
        \Illuminate\Support\Facades\Log::channel('rentri')->error('rentri.sync-codifiche.failed', [
            'scheduled_at' => now()->toIso8601String(),
        ]);
    });
