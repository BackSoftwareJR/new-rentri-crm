<?php

namespace App\Domain\Notifications;

use App\Enums\NotificationEvent;
use App\Jobs\SendNotificationJob;
use App\Mail\NotificationTestMail;
use App\Models\User;
use App\Notifications\AppNotification;
use App\Support\Logging\StructuredLogService;
use Illuminate\Mail\Mailable;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Mail;

class NotificationService
{
    public function __construct(
        private readonly NotificationPreferenceService $preferences,
        private readonly MailTransportRuntimeService $mailRuntime,
        private readonly StructuredLogService $logger,
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
                'event' => $event->value,
                'module' => $event->module(),
                'reason' => 'disabled',
            ]);

            return false;
        }

        $recipient = $recipient ?? (string) config('notifications.default_recipient');
        $logContext = array_merge([
            'event' => $event->value,
            'module' => $event->module(),
            'recipient' => $recipient,
            'subject' => $this->subjectFromMailable($mailable),
            'mail_mode' => $this->mailRuntime->modeLabel(),
            'mailer' => $this->mailRuntime->effectiveMailerName(),
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
                'event' => 'notification.test',
                'module' => 'notifications',
                'test' => true,
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
            'mailer' => $this->mailRuntime->effectiveMailerName(),
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

    // -------------------------------------------------------------------------
    // In-app (database) notifications
    // -------------------------------------------------------------------------

    /**
     * Store an in-app notification for one or more users.
     *
     * If `$notifiable` is null, the notification is sent to all users with
     * the 'admin' or 'segreteria' role.
     *
     * @param  User|list<User>|null  $notifiable
     * @param  array<string, mixed>  $context
     */
    public function notifyInApp(
        NotificationEvent $event,
        string $title,
        User|array|null $notifiable = null,
        ?string $body = null,
        ?string $url = null,
        array $context = [],
    ): void {
        $users = $this->resolveNotifiable($notifiable);

        if ($users->isEmpty()) {
            return;
        }

        $notification = new AppNotification($event, $title, $body, $url, $context);

        foreach ($users as $user) {
            $user->notify($notification);
        }

        $this->log('notification.in_app_dispatched', [
            'event' => $event->value,
            'title' => $title,
            'users_count' => $users->count(),
        ]);
    }

    /**
     * Return unread notifications for a user (last 10).
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function unreadForUser(User $user, int $limit = 10): Collection
    {
        return $user->unreadNotifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    /**
     * Return all notifications for a user (last $limit, read + unread).
     *
     * @return Collection<int, DatabaseNotification>
     */
    public function allForUser(User $user, int $limit = 10): Collection
    {
        return $user->notifications()
            ->latest()
            ->limit($limit)
            ->get();
    }

    public function markAllReadForUser(User $user): void
    {
        $user->unreadNotifications()->update(['read_at' => now()]);
    }

    public function unreadCountForUser(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    /**
     * @param  User|list<User>|null  $notifiable
     * @return Collection<int, User>
     */
    private function resolveNotifiable(User|array|null $notifiable): Collection
    {
        if ($notifiable instanceof User) {
            return collect([$notifiable]);
        }

        if (is_array($notifiable) && $notifiable !== []) {
            return collect($notifiable);
        }

        // Default: notify all admin + segreteria users
        return User::role(['admin', 'segreteria'])->get();
    }

    /** @param  array<string, mixed>  $context */
    private function log(string $action, array $context): void
    {
        $this->logger->info('operatore', $action, ucfirst(str_replace('.', ' ', $action)), [
            'extra' => $context,
        ]);
    }
}
