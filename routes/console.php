<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

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
