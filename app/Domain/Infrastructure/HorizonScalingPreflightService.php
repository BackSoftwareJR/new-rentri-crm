<?php

namespace App\Domain\Infrastructure;

use App\Models\RentriTransazione;
use App\Support\Horizon\HorizonMonitorService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Preflight scaling Horizon / queue (Sprint 107).
 */
class HorizonScalingPreflightService
{
    public function __construct(
        private readonly HorizonMonitorService $horizonMonitor,
    ) {}

    public function queueConnection(): string
    {
        return (string) config('queue.default', 'sync');
    }

    public function maxWorkerProcesses(): int
    {
        $env = config('app.env', 'local');
        $defaults = config('horizon.defaults.supervisor-1.maxProcesses', 1);
        $envOverride = config("horizon.environments.{$env}.supervisor-1.maxProcesses");

        return (int) ($envOverride ?? $defaults);
    }

    public function notificationsQueued(): bool
    {
        return (bool) config('notifications.queue', false);
    }

    public function horizonInstalled(): bool
    {
        return $this->horizonMonitor->isInstalled();
    }

    public function failedJobsCount(): int
    {
        if (! Schema::hasTable('failed_jobs')) {
            return 0;
        }

        return (int) DB::table('failed_jobs')->count();
    }

    public function pendingQueueJobsCount(): int
    {
        if (! Schema::hasTable('jobs')) {
            return 0;
        }

        return (int) DB::table('jobs')->whereNull('reserved_at')->count();
    }

    public function pendingRetryTransazioniCount(): int
    {
        if (! Schema::hasTable('rentri_transazioni')) {
            return 0;
        }

        return (int) RentriTransazione::query()
            ->where('stato', 'errore')
            ->whereNotNull('next_retry_at')
            ->whereNull('dead_letter_at')
            ->count();
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function checklist(): array
    {
        $connection = $this->queueConnection();
        $isProduction = config('app.env') === 'production';
        $failedJobs = $this->failedJobsCount();
        $retryPending = $this->pendingRetryTransazioniCount();
        $failedThreshold = (int) config('infrastructure.horizon.failed_jobs_warn_threshold', 0);

        return [
            $this->item(
                'horizon_installed',
                'Laravel Horizon installato',
                $this->horizonInstalled(),
                'composer require laravel/horizon',
            ),
            $this->item(
                'queue_redis',
                'Queue connection Redis (consigliato con Horizon)',
                $connection === 'redis' || ! $isProduction,
                'Produzione: QUEUE_CONNECTION=redis.',
            ),
            $this->item(
                'notifications_queue',
                'NOTIFICATIONS_QUEUE=true per volume email',
                $this->notificationsQueued() || ! config('notifications.live', false),
                'Abilitare coda notifiche quando NOTIFICATIONS_LIVE=true.',
            ),
            $this->item(
                'worker_processes',
                sprintf('Worker maxProcesses ≥ %d (env %s)', $this->recommendedMinWorkers(), config('app.env')),
                $this->maxWorkerProcesses() >= $this->recommendedMinWorkers() || ! $isProduction,
                'config/horizon.php environments.production.supervisor-1.maxProcesses',
            ),
            $this->item(
                'failed_jobs_clear',
                sprintf('Failed jobs ≤ %d', $failedThreshold),
                $failedJobs <= $failedThreshold,
                $failedJobs > 0
                    ? sprintf('%d failed jobs in coda — verificare Horizon.', $failedJobs)
                    : 'Nessun failed job pendente.',
            ),
            $this->item(
                'retry_jobs',
                'Retry RENTRI pianificati sotto controllo',
                $retryPending === 0 || $retryPending <= (int) config('infrastructure.horizon.retry_warn_threshold', 10),
                $retryPending > 0
                    ? sprintf('%d transazioni in retry pianificato.', $retryPending)
                    : 'Nessun retry RENTRI pendente.',
            ),
            $this->item(
                'horizon_dashboard',
                'Dashboard Horizon raggiungibile (admin)',
                $this->horizonInstalled(),
                $this->horizonMonitor->dashboardUrl(),
            ),
        ];
    }

    public function isReadyForProductionVolume(): bool
    {
        return collect($this->checklist())->every(fn (array $item): bool => $item['ok']);
    }

    /**
     * @return array{
     *     ready: bool,
     *     queue_connection: string,
     *     max_workers: int,
     *     notifications_queue: bool,
     *     failed_jobs: int,
     *     pending_jobs: int,
     *     retry_pending: int,
     *     horizon_url: string
     * }
     */
    public function summary(): array
    {
        return [
            'ready'               => $this->isReadyForProductionVolume(),
            'queue_connection'    => $this->queueConnection(),
            'max_workers'         => $this->maxWorkerProcesses(),
            'notifications_queue' => $this->notificationsQueued(),
            'failed_jobs'         => $this->failedJobsCount(),
            'pending_jobs'        => $this->pendingQueueJobsCount(),
            'retry_pending'       => $this->pendingRetryTransazioniCount(),
            'horizon_url'         => $this->horizonMonitor->dashboardUrl(),
        ];
    }

    public function recommendedMinWorkers(): int
    {
        return (int) config('infrastructure.horizon.min_workers_production', 3);
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string}
     */
    private function item(string $key, string $label, bool $ok, ?string $hint): array
    {
        return compact('key', 'label', 'ok', 'hint');
    }
}
