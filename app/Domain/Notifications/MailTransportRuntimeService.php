<?php

namespace App\Domain\Notifications;

use App\Support\Demo\DemoContext;

class MailTransportRuntimeService
{
    public function isLive(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return false;
        }

        return (bool) config('notifications.live', false);
    }

    public function modeLabel(): string
    {
        return $this->isLive() ? 'live' : 'stub';
    }

    public function modeDisplayLabel(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'Email demo offline',
            'stub'    => 'Notifiche stub (log)',
            default   => 'SMTP live',
        };
    }

    public function modeDisplayVariant(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'warning',
            'stub'    => 'info',
            default   => 'success',
        };
    }

    /**
     * @return 'offline'|'stub'|'live'
     */
    public function modeKind(): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        return $this->isLive() ? 'live' : 'stub';
    }

    public function shouldSendMail(): bool
    {
        return $this->isLive();
    }

    public function effectiveMailerName(): string
    {
        if (! $this->isLive()) {
            return 'log';
        }

        return (string) config('mail.default', 'smtp');
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function preflightChecklist(): array
    {
        if (! $this->isLive()) {
            return [];
        }

        $mailer = (string) config('mail.default', 'smtp');
        $from = (string) config('mail.from.address', '');
        $host = (string) config("mail.mailers.{$mailer}.host", '');

        return [
            [
                'key'   => 'mail_from',
                'label' => 'Indirizzo mittente configurato (MAIL_FROM_ADDRESS)',
                'ok'    => $from !== '' && $from !== 'hello@example.com',
                'hint'  => 'Impostare MAIL_FROM_ADDRESS con email operativa.',
            ],
            [
                'key'   => 'mail_host',
                'label' => 'Host SMTP configurato (MAIL_HOST)',
                'ok'    => $host !== '' && $host !== '127.0.0.1',
                'hint'  => 'Impostare MAIL_HOST, MAIL_PORT, MAIL_USERNAME, MAIL_PASSWORD.',
            ],
            [
                'key'   => 'mail_mailer',
                'label' => 'Mailer predefinito SMTP (MAIL_MAILER=smtp)',
                'ok'    => $mailer === 'smtp',
                'hint'  => 'Per produzione usare MAIL_MAILER=smtp.',
            ],
        ];
    }

    public function preflightReady(): bool
    {
        $items = $this->preflightChecklist();

        if ($items === []) {
            return true;
        }

        return collect($items)->every(fn (array $item) => $item['ok']);
    }
}
