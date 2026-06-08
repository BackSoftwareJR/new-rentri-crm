<?php

namespace App\Domain\Infrastructure;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;
use App\Services\Rentri\RentriCertificateService;
use App\Support\Horizon\QueueWorkerStatusService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Schema;

class ApplicationHealthService
{
    public const SCHEDULER_HEARTBEAT_KEY = 'health:scheduler:last_run';

    public const SCHEDULER_MAX_AGE_HOURS = 25;

    public function __construct(
        private readonly QueueWorkerStatusService $queueWorkers,
        private readonly RentriRuntimeModeService $rentriRuntime,
        private readonly RentriCertificateService $rentriCert,
    ) {}

    /**
     * @return array{
     *   status: string,
     *   checked_at: string,
     *   checks: array<string, array{status: string, message: string}>
     * }
     */
    public function diagnose(): array
    {
        $checks = [
            'database'         => $this->checkDatabase(),
            'redis'            => $this->checkRedis(),
            'queue_workers'    => $this->checkQueueWorkers(),
            'horizon'          => $this->checkHorizon(),
            'storage_writable' => $this->checkStorageWritable(),
            'rentri_cert'      => $this->checkRentriCertExpiry(),
            'scheduler'        => $this->checkSchedulerHeartbeat(),
        ];

        $degraded = collect($checks)->contains(fn (array $check) => $check['status'] === 'fail');

        return [
            'status'     => $degraded ? 'degraded' : 'healthy',
            'checked_at' => now()->toIso8601String(),
            'checks'     => $checks,
        ];
    }

    public function isHealthy(): bool
    {
        return $this->diagnose()['status'] === 'healthy';
    }

    /**
     * Used by Laravel /up (DiagnosingHealth) — throws on bootstrap-critical failures.
     */
    public function assertBootstrapHealthy(): void
    {
        foreach (['database' => $this->checkDatabase(), 'redis' => $this->checkRedis()] as $name => $check) {
            if ($check['status'] === 'fail') {
                throw new \RuntimeException(sprintf('%s: %s', $name, $check['message']));
            }
        }
    }

    public function recordSchedulerHeartbeat(): void
    {
        Cache::put(self::SCHEDULER_HEARTBEAT_KEY, now()->toIso8601String(), now()->addDays(3));
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkDatabase(): array
    {
        try {
            if (! Schema::hasTable('migrations')) {
                return $this->result('fail', 'Tabella migrations assente — eseguire php artisan migrate.');
            }

            DB::connection()->getPdo();

            return $this->result('ok', 'Connessione database OK ('.config('database.default').').');
        } catch (\Throwable $e) {
            return $this->result('fail', 'Database non raggiungibile: '.$e->getMessage());
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkRedis(): array
    {
        if (! $this->expectsRedis()) {
            return $this->result('ok', 'Redis non richiesto (driver cache/queue: '.config('cache.default').'/'.config('queue.default').').');
        }

        try {
            $pong = Redis::connection()->ping();

            if (is_bool($pong) && $pong) {
                return $this->result('ok', 'Connessione Redis OK.');
            }

            if (is_string($pong) && strtoupper($pong) === 'PONG') {
                return $this->result('ok', 'Connessione Redis OK.');
            }

            return $this->result('fail', 'Redis ping non ha risposto PONG.');
        } catch (\Throwable $e) {
            return $this->result('fail', 'Redis non raggiungibile: '.$e->getMessage());
        }
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkQueueWorkers(): array
    {
        $snapshot = $this->queueWorkers->snapshot();
        $connection = $snapshot['connection'];

        if ($connection === 'sync') {
            return $this->result('ok', 'Queue in modalità sync (nessun worker richiesto).');
        }

        if ($connection === 'redis' && ! $snapshot['horizon_active']) {
            return $this->result('fail', $snapshot['label'].' — '.$snapshot['pending'].' job in coda.');
        }

        return $this->result('ok', $snapshot['label'].' — '.$snapshot['pending'].' job in coda.');
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkHorizon(): array
    {
        $snapshot = $this->queueWorkers->snapshot();

        if (config('queue.default') !== 'redis') {
            return $this->result('ok', 'Horizon non richiesto (queue: '.config('queue.default').').');
        }

        if ($snapshot['horizon_active']) {
            return $this->result('ok', 'Horizon attivo.');
        }

        return $this->result('fail', 'Horizon non in esecuzione — avviare php artisan horizon.');
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkStorageWritable(): array
    {
        $paths = [
            storage_path('app'),
            storage_path('framework/cache'),
            storage_path('logs'),
        ];

        foreach ($paths as $path) {
            if (! is_dir($path) || ! is_writable($path)) {
                return $this->result('fail', 'Storage non scrivibile: '.$path);
            }
        }

        return $this->result('ok', 'Storage scrivibile (app, framework/cache, logs).');
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkRentriCertExpiry(): array
    {
        if (! Schema::hasTable('rentri_settings')) {
            return $this->result('ok', 'Tabella rentri_settings assente — skip cert check.');
        }

        $settings = RentriSetting::query()->where('is_demo', false)->get();

        if ($settings->isEmpty()) {
            return $this->result('warn', 'Nessuna configurazione RENTRI produzione — cert non verificato.');
        }

        $expired = [];
        $missing = [];
        $stubOnly = true;

        foreach ($settings as $setting) {
            if ($this->rentriRuntime->isApiStub($setting)) {
                continue;
            }

            $stubOnly = false;

            if (blank($setting->cert_path_encrypted)) {
                $missing[] = $setting->id;

                continue;
            }

            if ($this->rentriCert->isExpired($setting)) {
                $expired[] = $setting->id;
            }
        }

        if ($stubOnly) {
            return $this->result('warn', 'API RENTRI in stub — scadenza certificato non applicabile.');
        }

        if ($expired !== []) {
            return $this->result('fail', 'Certificato RENTRI scaduto per setting ID: '.implode(', ', $expired).'.');
        }

        if ($missing !== []) {
            return $this->result('fail', 'Certificato RENTRI mancante per setting ID: '.implode(', ', $missing).'.');
        }

        return $this->result('ok', 'Certificati RENTRI produzione validi.');
    }

    /**
     * @return array{status: string, message: string}
     */
    public function checkSchedulerHeartbeat(): array
    {
        if (! app()->environment('production', 'staging')) {
            return $this->result('ok', 'Heartbeat scheduler non richiesto in '.config('app.env').'.');
        }

        $lastRun = Cache::get(self::SCHEDULER_HEARTBEAT_KEY);

        if (! is_string($lastRun) || $lastRun === '') {
            return $this->result('fail', 'Scheduler mai eseguito — verificare cron * * * * * php artisan schedule:run.');
        }

        $ageHours = Carbon::parse($lastRun)->diffInHours(now());

        if ($ageHours >= self::SCHEDULER_MAX_AGE_HOURS) {
            return $this->result('fail', sprintf(
                'Ultimo schedule:run %.1f ore fa (soglia %dh).',
                $ageHours,
                self::SCHEDULER_MAX_AGE_HOURS,
            ));
        }

        return $this->result('ok', sprintf('Scheduler attivo — ultimo run %.1f ore fa.', $ageHours));
    }

    private function expectsRedis(): bool
    {
        $cacheStore = (string) config('cache.default', 'array');
        $queueConnection = (string) config('queue.default', 'sync');
        $sessionDriver = (string) config('session.driver', 'file');

        return in_array('redis', [$cacheStore, $queueConnection, $sessionDriver], true)
            || config('dashboard.kpi_cache.store') === 'redis';
    }

    /**
     * @return array{status: string, message: string}
     */
    private function result(string $status, string $message): array
    {
        return ['status' => $status, 'message' => $message];
    }
}
