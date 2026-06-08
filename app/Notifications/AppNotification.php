<?php

namespace App\Notifications;

use App\Enums\NotificationEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

/**
 * Generic in-app notification stored in the database notifications table.
 *
 * The `data` JSON column stores:
 *   - event:    NotificationEvent value (string)
 *   - title:    short human-readable title
 *   - body:     optional longer description
 *   - url:      optional action URL
 *   - module:   module key (e.g. 'bonifica', 'rentri')
 *   - context:  arbitrary extra data
 */
class AppNotification extends Notification
{
    use Queueable;

    /** @param  array<string, mixed>  $context */
    public function __construct(
        public readonly NotificationEvent $event,
        public readonly string $title,
        public readonly ?string $body = null,
        public readonly ?string $url = null,
        public readonly array $context = [],
    ) {}

    /** @return list<string> */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /** @return array<string, mixed> */
    public function toDatabase(object $notifiable): array
    {
        return [
            'event' => $this->event->value,
            'module' => $this->event->module(),
            'title' => $this->title,
            'body' => $this->body,
            'url' => $this->url,
            'context' => $this->context,
        ];
    }
}
