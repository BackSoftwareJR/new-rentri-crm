<?php

namespace App\Domain\Notifications;

/**
 * Preflight volume SMTP notifiche (Sprint 107).
 */
class SmtpVolumePreflightService
{
    public function __construct(
        private readonly MailTransportRuntimeService $mailRuntime,
    ) {}

    public function isLive(): bool
    {
        return $this->mailRuntime->isLive();
    }

    public function rateLimitPerMinute(): ?int
    {
        $limit = config('notifications.smtp_rate_limit_per_minute');

        return is_numeric($limit) ? (int) $limit : null;
    }

    public function dailyCap(): ?int
    {
        $cap = config('notifications.smtp_daily_cap');

        return is_numeric($cap) ? (int) $cap : null;
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool}>
     */
    public function checklist(): array
    {
        if (! $this->isLive()) {
            return [
                $this->item(
                    'notifications_live',
                    'NOTIFICATIONS_LIVE=false (stub — volume N/A)',
                    true,
                    'Abilitare NOTIFICATIONS_LIVE=true per SMTP produzione.',
                    false,
                ),
            ];
        }

        $mailPreflight = $this->mailRuntime->preflightChecklist();
        $items = array_map(
            fn (array $row): array => $this->item(
                $row['key'],
                $row['label'],
                $row['ok'],
                $row['hint'],
                false,
            ),
            $mailPreflight,
        );

        $items[] = $this->item(
            'notifications_queue',
            'NOTIFICATIONS_QUEUE=true (invio async via Horizon)',
            (bool) config('notifications.queue', false),
            'Raccomandato per volume > 50 email/giorno.',
            false,
        );

        $items[] = $this->item(
            'rate_limit_doc',
            'Rate limit SMTP documentato (relay provider)',
            $this->rateLimitDocPresent(),
            'Vedi HORIZON-SCALING-RUNBOOK.md § SMTP volume.',
            true,
        );

        $items[] = $this->item(
            'daily_cap',
            'Daily cap configurato (NOTIFICATIONS_SMTP_DAILY_CAP)',
            $this->dailyCap() !== null,
            'Opzionale — limita invii/giorno lato app.',
            true,
        );

        return $items;
    }

    public function isReadyForProductionVolume(): bool
    {
        return collect($this->checklist())
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    /**
     * @return array{
     *     ready: bool,
     *     mode: string,
     *     queued: bool,
     *     rate_limit_per_minute: ?int,
     *     daily_cap: ?int,
     *     ok: int,
     *     total: int
     * }
     */
    public function summary(): array
    {
        $required = collect($this->checklist())->reject(fn (array $i): bool => $i['optional']);

        return [
            'ready'                 => $this->isReadyForProductionVolume(),
            'mode'                  => $this->mailRuntime->modeDisplayLabel(),
            'queued'                => (bool) config('notifications.queue', false),
            'rate_limit_per_minute' => $this->rateLimitPerMinute(),
            'daily_cap'             => $this->dailyCap(),
            'ok'                    => $required->where('ok', true)->count(),
            'total'                 => $required->count(),
        ];
    }

    public function rateLimitDocPresent(): bool
    {
        $path = base_path('docs/HORIZON-SCALING-RUNBOOK.md');

        if (! is_file($path)) {
            return false;
        }

        $content = file_get_contents($path);

        return str_contains($content, 'SMTP') && str_contains($content, 'rate');
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string, optional: bool}
     */
    private function item(
        string $key,
        string $label,
        bool $ok,
        ?string $hint,
        bool $optional,
    ): array {
        return compact('key', 'label', 'ok', 'hint', 'optional');
    }
}
