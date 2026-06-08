<?php

namespace App\Domain\Infrastructure;

use Carbon\Carbon;

/**
 * Preflight HA multi-istanza + backup drill (Sprint 108).
 */
class HaBackupPreflightService
{
    public const RUNBOOK_DOC = 'docs/HA-BACKUP-DRILL-RUNBOOK.md';

    public const REDIS_SESSION_DOC = 'docs/REDIS-SESSION-PREP.md';

    public function sessionDriver(): string
    {
        return (string) config('session.driver', 'database');
    }

    public function isRedisSession(): bool
    {
        return $this->sessionDriver() === 'redis';
    }

    public function isProduction(): bool
    {
        return config('app.env') === 'production';
    }

    public function backupScheduleEnabled(): bool
    {
        return (bool) config('infrastructure.backup.schedule_enabled', false);
    }

    public function backupRetentionDays(): int
    {
        return (int) config('infrastructure.backup.retention_days', 30);
    }

    public function lastDrillAt(): ?Carbon
    {
        $raw = config('infrastructure.backup.last_drill_at');

        if (blank($raw)) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @return array{rpo_minutes: int, rto_minutes: int, rpo_label: string, rto_label: string}
     */
    public function rpoRtoTargets(): array
    {
        $rpo = (int) config('infrastructure.ha.rpo_minutes', 60);
        $rto = (int) config('infrastructure.ha.rto_minutes', 240);

        return [
            'rpo_minutes' => $rpo,
            'rto_minutes' => $rto,
            'rpo_label'   => sprintf('RPO ≤ %dh (backup schedule)', (int) ceil($rpo / 60)),
            'rto_label'   => sprintf('RTO ≤ %dh (restore drill)', (int) ceil($rto / 60)),
        ];
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function checklist(): array
    {
        $isProduction = $this->isProduction();
        $lastDrill = $this->lastDrillAt();
        $drillIntervalMonths = (int) config('infrastructure.ha.quarterly_drill_months', 3);
        $drillDue = $lastDrill === null
            || $lastDrill->lt(now()->subMonths($drillIntervalMonths));

        return [
            $this->item(
                'backup_schedule',
                'Backup DB schedulato (DB_BACKUP_SCHEDULE_ENABLED)',
                $this->backupScheduleEnabled() || ! $isProduction,
                'Abilitare cron backup + DB_BACKUP_STORAGE_PATH.',
                false,
                'backup',
            ),
            $this->item(
                'backup_retention',
                sprintf('Retention backup ≥ %d giorni', $this->backupRetentionDays()),
                $this->backupRetentionDays() >= 7 || ! $isProduction,
                'DB_BACKUP_RETENTION_DAYS in .env.',
                false,
                'backup',
            ),
            $this->item(
                'backup_storage',
                'Path storage backup configurato',
                filled(config('infrastructure.backup.storage_path')) || ! $isProduction,
                'DB_BACKUP_STORAGE_PATH (S3 o path locale cifrato).',
                false,
                'backup',
            ),
            $this->item(
                'restore_drill_runbook',
                'Runbook restore drill documentato',
                is_file(base_path(self::RUNBOOK_DOC)),
                self::RUNBOOK_DOC,
                false,
                'backup',
            ),
            $this->item(
                'quarterly_drill',
                sprintf('Restore drill eseguito (< %d mesi)', $drillIntervalMonths),
                ! $drillDue || ! $isProduction,
                $lastDrill
                    ? 'Ultimo drill: '.$lastDrill->format('d/m/Y').' — aggiornare DB_BACKUP_LAST_DRILL_AT.'
                    : 'Impostare DB_BACKUP_LAST_DRILL_AT dopo primo drill.',
                false,
                'backup',
            ),
            $this->item(
                'redis_session',
                'Sessioni Redis (multi-istanza HA)',
                $this->isRedisSession() || ! $isProduction,
                'SESSION_DRIVER=redis — vedi REDIS-SESSION-PREP.md.',
                false,
                'ha',
            ),
            $this->item(
                'redis_session_doc',
                'Documentazione Redis session cluster aggiornata',
                $this->redisSessionDocUpdated(),
                self::REDIS_SESSION_DOC.' § multi-istanza.',
                false,
                'ha',
            ),
            $this->item(
                'queue_redis',
                'Queue Redis (coerenza job multi-nodo)',
                config('queue.default') === 'redis' || ! $isProduction,
                'QUEUE_CONNECTION=redis con Horizon.',
                false,
                'ha',
            ),
            $this->item(
                'rpo_rto_doc',
                'Target RPO/RTO definiti in runbook',
                $this->runbookContainsRpoRto(),
                sprintf('RPO %d min · RTO %d min', ...array_values(array_slice($this->rpoRtoTargets(), 0, 2))),
                false,
                'ha',
            ),
        ];
    }

    public function isReadyForHaProduction(): bool
    {
        return collect($this->checklist())
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    /**
     * @return array{
     *     ready: bool,
     *     session_driver: string,
     *     redis_session: bool,
     *     backup_scheduled: bool,
     *     last_drill: ?string,
     *     rpo_minutes: int,
     *     rto_minutes: int,
     *     ok: int,
     *     total: int
     * }
     */
    public function summary(): array
    {
        $required = collect($this->checklist())->reject(fn (array $i): bool => $i['optional']);
        $targets = $this->rpoRtoTargets();

        return [
            'ready'            => $this->isReadyForHaProduction(),
            'session_driver'   => $this->sessionDriver(),
            'redis_session'    => $this->isRedisSession(),
            'backup_scheduled' => $this->backupScheduleEnabled(),
            'last_drill'       => $this->lastDrillAt()?->toDateString(),
            'rpo_minutes'      => $targets['rpo_minutes'],
            'rto_minutes'      => $targets['rto_minutes'],
            'ok'               => $required->where('ok', true)->count(),
            'total'            => $required->count(),
        ];
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function failoverSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Verificare health /up su istanza secondaria',
                'detail' => 'Load balancer deve escludere nodo unhealthy.',
            ],
            [
                'step'   => 2,
                'action' => 'Confermare Redis session/queue raggiungibile',
                'detail' => 'Failover ElastiCache o replica Redis promossa.',
            ],
            [
                'step'   => 3,
                'action' => 'Horizon attivo su almeno un nodo',
                'detail' => 'php artisan horizon — supervisor systemd.',
            ],
            [
                'step'   => 4,
                'action' => 'Smoke login + Livewire + rentri:monitor',
                'detail' => 'Verificare sessione persistente cross-nodo.',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function documentPaths(): array
    {
        return [
            'ha_runbook'        => self::RUNBOOK_DOC,
            'failover_drill'    => HaFailoverDrillService::RUNBOOK_DOC,
            'redis_session'     => self::REDIS_SESSION_DOC,
            'go_live'        => 'docs/GO-LIVE-OPERATIVO.md',
            'monitoring'     => 'docs/MONITORING-CICLO-3.md',
            'horizon'        => 'docs/HORIZON-SCALING-RUNBOOK.md',
        ];
    }

    private function redisSessionDocUpdated(): bool
    {
        $path = base_path(self::REDIS_SESSION_DOC);

        if (! is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);

        return str_contains($content, 'multi-istanza')
            || str_contains($content, 'Multi-istanza');
    }

    private function runbookContainsRpoRto(): bool
    {
        $path = base_path(self::RUNBOOK_DOC);

        if (! is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);

        return str_contains($content, 'RPO') && str_contains($content, 'RTO');
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}
     */
    private function item(
        string $key,
        string $label,
        bool $ok,
        ?string $hint,
        bool $optional,
        string $group,
    ): array {
        return compact('key', 'label', 'ok', 'hint', 'optional', 'group');
    }
}
