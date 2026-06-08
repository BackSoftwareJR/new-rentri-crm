<?php

namespace App\Console\Commands;

use App\Domain\Logging\ApplicationLogQueryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class LogsHealthCommand extends Command
{
    protected $signature = 'logs:health';

    protected $description = 'Verifica canali log scrivibili, spazio disco e ultimo errore critico';

    /** @var list<string> */
    private const CHANNELS = ['stack', 'rentri', 'security', 'integration', 'business'];

    public function handle(ApplicationLogQueryService $logs): int
    {
        $this->info('Health check logging produzione');
        $this->newLine();

        $allOk = true;

        foreach (self::CHANNELS as $channel) {
            $path = $this->resolveChannelPath($channel);

            if ($path === null) {
                $this->line(sprintf('[SKIP] %s — canale stack/composito', $channel));

                continue;
            }

            $dir = dirname($path);
            $writable = is_dir($dir) && is_writable($dir);

            if ($writable) {
                $this->line(sprintf('[OK] %s scrivibile (%s)', $channel, $path));
            } else {
                $allOk = false;
                $this->error(sprintf('[FAIL] %s non scrivibile (%s)', $channel, $path));
            }
        }

        $storagePath = storage_path('logs');
        $freeBytes = @disk_free_space($storagePath);
        if ($freeBytes !== false) {
            $freeMb = round($freeBytes / 1024 / 1024, 1);
            $hint = $freeMb < 512 ? 'WARN — spazio basso' : 'OK';
            $this->newLine();
            $this->line(sprintf('Spazio disco storage/logs: %.1f MB (%s)', $freeMb, $hint));

            if ($freeMb < 512) {
                $allOk = false;
            }
        }

        $lastError = $logs->lastCriticalError();
        $this->newLine();

        if ($lastError === null) {
            $this->line('[OK] Nessun errore critico in application_logs.');
        } else {
            $this->warn(sprintf(
                'Ultimo errore critico: #%d %s — %s (%s)',
                $lastError->id,
                strtoupper($lastError->level),
                $lastError->message,
                $lastError->created_at?->format('Y-m-d H:i:s') ?? '—',
            ));
        }

        $persist = (bool) config('application_log.persist_to_database', true);
        $this->newLine();
        $this->line('Persistenza DB: '.($persist ? 'attiva' : 'disattiva'));
        $this->line('Retention: '.(int) config('application_log.retention_days', 90).' giorni');

        if (! $allOk) {
            $this->newLine();
            $this->error('Health check logging — problemi rilevati.');

            return self::FAILURE;
        }

        $this->newLine();
        $this->info('Health check logging — OK.');

        return self::SUCCESS;
    }

    private function resolveChannelPath(string $channel): ?string
    {
        if ($channel === 'stack') {
            return storage_path('logs/laravel.log');
        }

        $configured = config("logging.channels.{$channel}");

        if (! is_array($configured)) {
            return null;
        }

        if (isset($configured['path'])) {
            return (string) $configured['path'];
        }

        if (($configured['driver'] ?? '') === 'stack') {
            return null;
        }

        return storage_path("logs/{$channel}.log");
    }
}
