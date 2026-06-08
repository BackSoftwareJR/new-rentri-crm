<?php

namespace App\Support\Horizon;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Laravel\Horizon\Contracts\MasterSupervisorRepository;

final class QueueWorkerStatusService
{
    public function __construct(
        private readonly HorizonMonitorService $horizon,
    ) {}

    /**
     * @return array{connection: string, label: string, status: string, pending: int, horizon_active: bool}
     */
    public function snapshot(): array
    {
        $connection = (string) config('queue.default', 'sync');

        if ($connection === 'sync') {
            return [
                'connection'      => $connection,
                'label'           => 'Esecuzione sincrona (nessun worker)',
                'status'          => 'sync',
                'pending'         => 0,
                'horizon_active'  => false,
            ];
        }

        $pending = $this->pendingJobs($connection);
        $horizonActive = $this->horizonActive();

        return [
            'connection'     => $connection,
            'label'          => $this->label($connection, $horizonActive),
            'status'         => $horizonActive ? 'running' : ($connection === 'redis' ? 'stopped' : 'worker'),
            'pending'        => $pending,
            'horizon_active' => $horizonActive,
        ];
    }

    private function label(string $connection, bool $horizonActive): string
    {
        if ($connection === 'redis' && $this->horizon->isInstalled()) {
            return $horizonActive
                ? 'Horizon attivo'
                : 'Horizon non in esecuzione — avviare `php artisan horizon`';
        }

        if ($connection === 'database') {
            return 'Queue worker database (queue:work)';
        }

        return 'Queue '.$connection;
    }

    private function pendingJobs(string $connection): int
    {
        try {
            if ($connection === 'database') {
                return (int) DB::table('jobs')->count();
            }

            if ($connection === 'redis') {
                $redis = Redis::connection(config('horizon.use', 'default'));
                $queues = ['default', 'rentri', 'notifications', 'exports'];
                $total = 0;

                foreach ($queues as $queue) {
                    $total += (int) $redis->llen('queues:'.$queue);
                }

                return $total;
            }
        } catch (\Throwable) {
            return 0;
        }

        return 0;
    }

    private function horizonActive(): bool
    {
        if (! $this->horizon->isInstalled() || config('queue.default') !== 'redis') {
            return false;
        }

        try {
            return count(app(MasterSupervisorRepository::class)->all()) > 0;
        } catch (\Throwable) {
            return false;
        }
    }
}
