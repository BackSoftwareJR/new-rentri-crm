<?php

namespace App\Domain\Notifications;

use App\Enums\NotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Mail\NotificationTestMail;
use Illuminate\Mail\Mailable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences,
        private readonly MailTransportRuntimeService $mailRuntime,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function dispatch(
        NotificationEvent $event,
        Mailable $mailable,
        ?string $recipient = null,
        array $context = [],
    ): bool {
        if (! $this->preferences->isEnabled($event)) {
            $this->log('notification.skipped', [
                'event'  => $event->value,
                'module' => $event->module(),
                'reason' => 'disabled',
            ]);

            return false;
        }

        $recipient = $recipient ?? (string) config('notifications.default_recipient');
        $logContext = array_merge([
            'event'     => $event->value,
            'module'    => $event->module(),
            'recipient' => $recipient,
            'subject'   => $this->subjectFromMailable($mailable),
            'mail_mode' => $this->mailRuntime->modeLabel(),
            'mailer'    => $this->mailRuntime->effectiveMailerName(),
        ], $context);

        if (config('notifications.queue')) {
            SendNotificationJob::dispatch($event, $mailable, $recipient, $logContext);
            $this->log('notification.queued', $logContext);

            return true;
        }

        return $this->deliver($mailable, $recipient, $logContext);
    }

    public function sendTestEmail(string $recipient, string $sentBy): bool
    {
        return $this->deliver(
            new NotificationTestMail($sentBy),
            $recipient,
            [
                'event'  => 'notification.test',
                'module' => 'notifications',
                'test'   => true,
            ],
        );
    }

    /**
     * @param  array<string, mixed>  $logContext
     */
    public function deliver(Mailable $mailable, string $recipient, array $logContext): bool
    {
        $logContext = array_merge([
            'mail_mode' => $this->mailRuntime->modeLabel(),
            'mailer'    => $this->mailRuntime->effectiveMailerName(),
        ], $logContext);

        if ($this->mailRuntime->shouldSendMail()) {
            Mail::mailer($this->mailRuntime->effectiveMailerName())
                ->to($recipient)
                ->send($mailable);
        }

        $this->log('notification.dispatched', $logContext);

        return true;
    }

    private function subjectFromMailable(Mailable $mailable): ?string
    {
        if (! method_exists($mailable, 'envelope')) {
            return null;
        }

        return $mailable->envelope()->subject;
    }

    /** @param  array<string, mixed>  $context */
    private function log(string $message, array $context): void
    {
        Log::channel((string) config('notifications.log_channel', 'notifications'))
            ->info($message, $context);
    }
}
