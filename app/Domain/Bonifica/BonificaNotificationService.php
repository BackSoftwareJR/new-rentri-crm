<?php

namespace App\Domain\Bonifica;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\BonificaPericolosiCompletataMail;
use App\Models\VfuRegistration;
use Carbon\Carbon;

class BonificaNotificationService
{
    public function __construct(
        private readonly NotificationService $notifications,
    ) {}

    public function notifyPericolosiCompletata(
        VfuRegistration $vfu,
        ?Carbon $deadline,
        bool $withinDeadline,
    ): void {
        $this->notifications->dispatch(
            NotificationEvent::BonificaPericolosiCompletata,
            new BonificaPericolosiCompletataMail($vfu, $deadline, $withinDeadline),
            config('services.bonifica.notify_email'),
            [
                'targa'           => $vfu->targa,
                'within_deadline' => $withinDeadline,
            ],
        );
    }
}
