<?php

namespace App\Jobs;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendNotificationJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    /**
     * @param  array<string, mixed>  $logContext
     */
    public function __construct(
        public NotificationEvent $event,
        public Mailable $mailable,
        public string $recipient,
        public array $logContext = [],
    ) {
        $this->onQueue('notifications');
    }

    public function handle(NotificationService $notifications): void
    {
        $notifications->deliver($this->mailable, $this->recipient, $this->logContext);
    }
}
