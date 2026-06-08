<?php

namespace App\Services\Pec;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class PecMailService
{
    private readonly string $host;

    private readonly int $port;

    private readonly ?string $username;

    private readonly ?string $password;

    private readonly string $fromAddress;

    private readonly string $fromName;

    public function __construct()
    {
        $this->host         = (string) config('pec.host');
        $this->port         = (int) config('pec.port');
        $this->username     = config('pec.username');
        $this->password     = config('pec.password');
        $this->fromAddress  = (string) config('pec.from.address');
        $this->fromName     = (string) config('pec.from.name');
    }

    public function isEnabled(): bool
    {
        return (bool) config('pec.enabled');
    }

    /**
     * @param  list<array{path: string, name?: string, mime?: string}|string>  $attachments
     */
    public function send(string $to, string $subject, string $body, array $attachments = []): bool
    {
        $logContext = [
            'to'              => $to,
            'subject'         => $subject,
            'attachment_count' => count($attachments),
            'mailer'          => 'pec',
            'host'            => $this->host,
            'port'            => $this->port,
            'from'            => $this->fromAddress,
            'mode'            => $this->isEnabled() ? 'live' : 'stub',
        ];

        if (! $this->isEnabled()) {
            Log::channel(config('notifications.log_channel', 'notifications'))
                ->info('pec.stub', $logContext);

            return true;
        }

        try {
            Mail::mailer('pec')->send([], [], function ($message) use ($to, $subject, $body, $attachments) {
                $message->to($to)
                    ->subject($subject)
                    ->from($this->fromAddress, $this->fromName)
                    ->html($body);

                foreach ($attachments as $attachment) {
                    if (is_string($attachment)) {
                        $message->attach($attachment);

                        continue;
                    }

                    $path = $attachment['path'] ?? null;
                    if (! is_string($path) || $path === '') {
                        continue;
                    }

                    $options = array_filter([
                        'as'   => $attachment['name'] ?? null,
                        'mime' => $attachment['mime'] ?? null,
                    ]);

                    $message->attach($path, $options);
                }
            });

            Log::channel(config('notifications.log_channel', 'notifications'))
                ->info('pec.sent', $logContext);

            return true;
        } catch (\Throwable $e) {
            Log::channel(config('notifications.log_channel', 'notifications'))
                ->error('pec.failed', array_merge($logContext, [
                    'error' => $e->getMessage(),
                ]));

            return false;
        }
    }
}
