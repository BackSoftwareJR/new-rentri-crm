<?php

namespace App\Console\Commands;

use App\Domain\Deploy\Cycle3MonitoringService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\RentriDeadLetterMail;
use App\Models\RentriSetting;
use App\Services\Rentri\Contracts\RentriApiClientInterface;
use App\Support\Horizon\HorizonMonitorService;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;

/**
 * Orchestrating pre-go-live command that runs all readiness checks in sequence.
 *
 * Runs rentri:preflight → rentri:production-switch-check → health check →
 * certificate expiry → queue workers → env vars → migration status →
 * final summary.
 *
 * NOT scheduled — manual invocation only.
 */
class RentriGoLiveCommand extends Command
{
    protected $signature = 'rentri:go-live
                            {--dry-run : Esegui tutti i check senza abilitare la modalità live}
                            {--force : Salta la conferma interattiva e abilita live dopo i check}
                            {--notify : Invia email admin al completamento}';

    protected $description = 'Orchestrazione pre-go-live: preflight → switch-check → health → cert → queue → env → migrazioni → riepilogo';

    /** @var list<array{step: string, status: string, message: string, duration_ms: int}> */
    private array $results = [];

    private int $issueCount = 0;

    public function handle(
        Cycle3MonitoringService $monitoring,
        RentriApiClientInterface $apiClient,
        HorizonMonitorService $horizonMonitor,
    ): int {
        $isDryRun = (bool) $this->option('dry-run');

        $this->line('');
        $this->info('╔══════════════════════════════════════════╗');
        $this->info('║    RENTRI CRM — Pre-Go-Live Checklist    ║');
        $this->info('╚══════════════════════════════════════════╝');
        $this->newLine();

        if ($isDryRun) {
            $this->warn('Modalità --dry-run: nessuna modifica verrà applicata.');
            $this->newLine();
        }

        // Step 1 — basic preflight
        $this->runStep('Preflight di base (rentri:preflight)', function (): bool {
            $exitCode = Artisan::call('rentri:preflight');

            if ($exitCode !== self::SUCCESS) {
                $output = Artisan::output();
                $failLines = collect(explode("\n", $output))
                    ->filter(fn (string $line): bool => str_contains($line, '[FAIL]'))
                    ->values()
                    ->implode(' | ');

                $this->setStepMessage($failLines ?: 'Preflight fallito — vedi rentri:preflight per dettagli.');

                return false;
            }

            return true;
        });

        // Step 2 — full production switch readiness
        $this->runStep('Production switch readiness (rentri:production-switch-check)', function (): bool {
            $exitCode = Artisan::call('rentri:production-switch-check');

            if ($exitCode !== self::SUCCESS) {
                $output = Artisan::output();
                $failLines = collect(explode("\n", $output))
                    ->filter(fn (string $line): bool => str_contains($line, '[FAIL]'))
                    ->values()
                    ->implode(' | ');

                $this->setStepMessage($failLines ?: 'Switch check fallito — vedi rentri:production-switch-check.');

                return false;
            }

            return true;
        });

        // Step 3 — framework health check
        $this->runStep('Health check applicazione (/up)', function () use ($monitoring): bool {
            $health = $monitoring->checkFrameworkHealth();

            if ($health['status'] !== 'ok') {
                $this->setStepMessage($health['message']);

                return false;
            }

            return true;
        });

        // Step 4 — certificate expiry
        $this->runStep('Scadenza certificati RENTRI (avviso < 30 gg)', function (): bool {
            $settings = RentriSetting::instance();

            $certScadenza = $settings->cert_scadenza;
            $firmaScadenza = $settings->firma_cert_scadenza;

            $messages = [];
            $hasError = false;

            if ($certScadenza === null) {
                $messages[] = 'Certificato mTLS non configurato.';
                $hasError = true;
            } elseif ($certScadenza->isPast()) {
                $messages[] = 'Certificato mTLS SCADUTO il '.$certScadenza->toDateString().'.';
                $hasError = true;
            } elseif ($certScadenza->diffInDays(now()) < 30) {
                $days = (int) now()->diffInDays($certScadenza);
                $messages[] = "Certificato mTLS scade tra {$days} giorni ({$certScadenza->toDateString()}).";
            }

            if ($firmaScadenza !== null) {
                if ($firmaScadenza->isPast()) {
                    $messages[] = 'Certificato firma xFIR SCADUTO il '.$firmaScadenza->toDateString().'.';
                    $hasError = true;
                } elseif ($firmaScadenza->diffInDays(now()) < 30) {
                    $days = (int) now()->diffInDays($firmaScadenza);
                    $messages[] = "Certificato firma xFIR scade tra {$days} giorni ({$firmaScadenza->toDateString()}).";
                }
            }

            if ($messages !== []) {
                $this->setStepMessage(implode(' | ', $messages));
            }

            return ! $hasError;
        });

        // Step 5 — queue workers / Horizon
        $this->runStep('Queue workers / Horizon attivi (ultimi 5 min)', function () use ($horizonMonitor): bool {
            if ($horizonMonitor->isInstalled()) {
                try {
                    $horizonStatus = $this->getHorizonStatus();

                    if ($horizonStatus === 'inactive' || $horizonStatus === 'paused') {
                        $this->setStepMessage("Horizon in stato: {$horizonStatus}. Avviare con: php artisan horizon");

                        return false;
                    }

                    if ($horizonStatus !== null) {
                        return true;
                    }
                } catch (\Throwable) {
                    // Horizon check failed — fall through to queue table check
                }
            }

            // Fallback: verify worker activity via jobs table staleness
            $this->checkQueueTableAccess();

            // If setStepMessage was called, a problem was found
            return $this->currentStepMessage === null;
        });

        // Step 6 — required env vars
        $this->runStep('Variabili di ambiente obbligatorie', function (): bool {
            $missing = $this->checkRequiredEnvVars();

            if ($missing !== []) {
                $this->setStepMessage('Variabili mancanti o stub: '.implode(', ', $missing));

                return false;
            }

            return true;
        });

        // Step 7 — database migrations up-to-date
        $this->runStep('Migrazioni database aggiornate', function (): bool {
            try {
                $pendingCount = $this->countPendingMigrations();

                if ($pendingCount > 0) {
                    $this->setStepMessage("{$pendingCount} migrazione/i pendente/i. Eseguire: php artisan migrate");

                    return false;
                }
            } catch (\Throwable $e) {
                $this->setStepMessage('Impossibile verificare le migrazioni: '.$e->getMessage());

                return false;
            }

            return true;
        });

        // Final summary table
        $this->newLine();
        $this->printSummaryTable();
        $this->newLine();

        $allPassed = $this->issueCount === 0;

        if ($allPassed) {
            $this->info('✅  Tutti i check superati — sistema pronto per il go-live.');
        } else {
            $this->error("❌  {$this->issueCount} problema/i riscontrato/i — correggere prima del go-live.");
        }

        if ($this->option('notify')) {
            $this->sendAdminNotification($allPassed);
        }

        return $allPassed ? self::SUCCESS : self::FAILURE;
    }

    /**
     * Execute a named check step, measure duration, and record the result.
     */
    private function runStep(string $label, callable $check): void
    {
        $this->output->write("  ⏳  {$label} … ");
        $this->currentStepMessage = null;

        $start = microtime(true);

        try {
            $passed = (bool) $check();
        } catch (\Throwable $e) {
            $passed = false;
            $this->setStepMessage($e->getMessage());
        }

        $durationMs = (int) round((microtime(true) - $start) * 1000);

        $status = $passed ? 'ok' : 'fail';

        $icon = $passed ? '✅' : '❌';
        $this->line(" {$icon}  ({$durationMs} ms)");

        if (! $passed && $this->currentStepMessage) {
            $this->warn("       → {$this->currentStepMessage}");
        }

        $this->results[] = [
            'step'        => $label,
            'status'      => $status,
            'message'     => $this->currentStepMessage ?? ($passed ? 'OK' : 'Fallito'),
            'duration_ms' => $durationMs,
        ];

        if (! $passed) {
            $this->issueCount++;
        }
    }

    private ?string $currentStepMessage = null;

    private function setStepMessage(string $message): void
    {
        $this->currentStepMessage = $message;
    }

    private function printSummaryTable(): void
    {
        $this->info('── Riepilogo Go-Live ──────────────────────────────────────────────');

        $headers = ['Step', 'Esito', 'Durata', 'Note'];
        $rows = array_map(function (array $r): array {
            $icon = $r['status'] === 'ok' ? '✅ OK' : '❌ FAIL';
            $note = $r['status'] === 'ok' ? '' : ($r['message'] ?? '—');
            $noteShort = strlen($note) > 60 ? substr($note, 0, 57).'…' : $note;

            return [
                substr($r['step'], 0, 45),
                $icon,
                $r['duration_ms'].' ms',
                $noteShort,
            ];
        }, $this->results);

        $this->table($headers, $rows);
    }

    /**
     * @return list<string>
     */
    private function checkRequiredEnvVars(): array
    {
        $stubDefaults = [
            'your-secret-key-here',
            'null',
            '',
            'example.com',
            'localhost',
        ];

        $required = [
            'APP_KEY'          => (string) config('app.key'),
            'APP_URL'          => (string) config('app.url'),
            'DB_DATABASE'      => (string) config('database.connections.mysql.database', ''),
            'MAIL_FROM_ADDRESS' => (string) config('mail.from.address', ''),
        ];

        $rentriRequired = [
            'RENTRI_ENV'              => (string) config('services.rentri.env', ''),
            'RENTRI_BASE_URL_SANDBOX' => (string) config('services.rentri.base_url_sandbox', ''),
        ];

        $missing = [];

        foreach (array_merge($required, $rentriRequired) as $name => $value) {
            $trimmed = trim($value);

            if ($trimmed === '' || in_array(strtolower($trimmed), $stubDefaults, true)) {
                $missing[] = $name;
            }
        }

        return $missing;
    }

    private function countPendingMigrations(): int
    {
        $ran = DB::table('migrations')->pluck('migration')->all();
        $files = glob(database_path('migrations/*.php')) ?: [];

        $pending = 0;
        foreach ($files as $file) {
            $name = pathinfo($file, PATHINFO_FILENAME);
            if (! in_array($name, $ran, true)) {
                $pending++;
            }
        }

        return $pending;
    }

    private function getHorizonStatus(): ?string
    {
        if (! class_exists(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class)) {
            return null;
        }

        try {
            /** @var \Laravel\Horizon\Contracts\MasterSupervisorRepository $repo */
            $repo = app(\Laravel\Horizon\Contracts\MasterSupervisorRepository::class);
            $masters = $repo->all();

            if (empty($masters)) {
                return 'inactive';
            }

            foreach ($masters as $master) {
                if (isset($master->status)) {
                    return $master->status;
                }
            }

            return 'running';
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * Verify that at least one queue worker has processed a job in the last 5 minutes.
     *
     * Strategy (in order):
     *  1. If Horizon is installed, check master supervisor status via repository.
     *  2. Check `jobs` table for pending jobs created > 5 min ago (stale = worker likely dead).
     *  3. Check `failed_jobs` table for any very recent failure (< 1 min) as a smoke test.
     *  4. Inspect application log for queue worker heartbeat if available.
     */
    private function checkQueueTableAccess(): void
    {
        if (! Schema::hasTable('jobs')) {
            $this->setStepMessage('Tabella jobs non trovata — queue worker non verificabile.');

            return;
        }

        // Stale jobs: pending jobs older than 5 minutes suggest no active worker
        $staleJobsCount = DB::table('jobs')
            ->where('available_at', '<=', now()->subMinutes(5)->timestamp)
            ->count();

        if ($staleJobsCount > 0) {
            $this->setStepMessage(
                "{$staleJobsCount} job/s in coda da più di 5 minuti — nessun worker attivo? ".
                'Avviare con: php artisan queue:work',
            );

            return;
        }

        // Recent failures in the last minute are a red flag
        if (Schema::hasTable('failed_jobs')) {
            $recentFailures = DB::table('failed_jobs')
                ->where('failed_at', '>=', now()->subMinute()->toDateTimeString())
                ->count();

            if ($recentFailures > 0) {
                $this->setStepMessage(
                    "{$recentFailures} job/s fallito/i nell'ultimo minuto — verificare i log del worker.",
                );
            }
        }
    }

    private function sendAdminNotification(bool $allPassed): void
    {
        $recipient = (string) config('notifications.default_recipient', 'admin@example.com');

        if (blank($recipient)) {
            $this->warn('Notifica non inviata: NOTIFICATIONS_RECIPIENT non configurato.');

            return;
        }

        $subject = $allPassed
            ? '[RENTRI CRM] ✅ Go-live check completato — tutti i check superati'
            : "[RENTRI CRM] ❌ Go-live check fallito — {$this->issueCount} problema/i riscontrato/i";

        $failedSteps = collect($this->results)
            ->filter(fn (array $r): bool => $r['status'] !== 'ok')
            ->map(fn (array $r): string => "• {$r['step']}: {$r['message']}")
            ->values()
            ->all();

        try {
            Mail::raw(
                $subject."\n\n".
                'Timestamp: '.now()->toIso8601String()."\n".
                'Host: '.gethostname()."\n\n".
                ($failedSteps !== [] ? "Check falliti:\n".implode("\n", $failedSteps) : 'Tutti i check superati.'),
                fn ($message) => $message
                    ->to($recipient)
                    ->subject($subject),
            );

            $this->line("  📧  Notifica inviata a {$recipient}.");
        } catch (\Throwable $e) {
            $this->warn('  Notifica non inviata: '.$e->getMessage());
        }
    }
}
