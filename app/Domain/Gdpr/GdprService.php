<?php

namespace App\Domain\Gdpr;

use App\Domain\Audit\ActivityLogService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\GdprDeletionRequestAdminMail;
use App\Models\User;
use App\Models\VfuRegistration;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Activity;

class GdprService
{
    private const DELETION_GRACE_DAYS = 30;

    public function __construct(
        private readonly ActivityLogService $audit,
        private readonly NotificationService $notifications,
    ) {}

    /** @return array<string, mixed> */
    public function exportUserData(User $user): array
    {
        $user->loadMissing(['roles', 'sito', 'pushSubscriptions']);

        return [
            'exported_at' => now()->toIso8601String(),
            'profile'     => [
                'id'              => $user->id,
                'name'            => $user->name,
                'email'           => $user->email,
                'active'          => $user->active,
                'roles'           => $user->getRoleNames()->values()->all(),
                'sito'            => $user->sito?->only(['id', 'nome']),
                'email_verified_at' => $user->email_verified_at?->toIso8601String(),
                'last_login_at'   => $user->last_login_at?->toIso8601String(),
                'created_at'      => $user->created_at?->toIso8601String(),
                'two_factor_enabled' => $user->hasTwoFactorEnabled(),
            ],
            'push_subscriptions' => $user->pushSubscriptions
                ->map(fn ($sub) => [
                    'id'          => $sub->id,
                    'endpoint'    => $sub->endpoint,
                    'device_name' => $sub->device_name,
                    'created_at'  => $sub->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'notifications' => $user->notifications()
                ->orderByDesc('created_at')
                ->limit(500)
                ->get()
                ->map(fn ($n) => [
                    'id'         => $n->id,
                    'type'       => $n->type,
                    'data'       => $n->data,
                    'read_at'    => $n->read_at?->toIso8601String(),
                    'created_at' => $n->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'activity_logs' => Activity::query()
                ->where(function ($q) use ($user) {
                    $q->where(function ($inner) use ($user) {
                        $inner->where('causer_type', User::class)
                            ->where('causer_id', $user->id);
                    })->orWhere(function ($inner) use ($user) {
                        $inner->where('subject_type', User::class)
                            ->where('subject_id', $user->id);
                    });
                })
                ->orderByDesc('created_at')
                ->limit(500)
                ->get()
                ->map(fn (Activity $a) => [
                    'id'          => $a->id,
                    'modulo'      => $a->log_name,
                    'description' => $a->description,
                    'properties'  => $a->properties?->toArray() ?? [],
                    'created_at'  => $a->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
            'vfu_assignments' => VfuRegistration::query()
                ->where('operatore_assegnato_id', $user->id)
                ->orderByDesc('created_at')
                ->limit(200)
                ->get()
                ->map(fn (VfuRegistration $vfu) => [
                    'id'     => $vfu->id,
                    'targa'  => $vfu->targa,
                    'telaio' => $vfu->telaio,
                    'marca'  => $vfu->marca,
                    'modello'=> $vfu->modello,
                    'stato'  => $vfu->stato?->value ?? (string) $vfu->stato,
                    'created_at' => $vfu->created_at?->toIso8601String(),
                ])
                ->values()
                ->all(),
        ];
    }

    public function requestDeletion(User $user, string $reason): void
    {
        if ($user->deletion_requested_at !== null) {
            throw new \InvalidArgumentException('Richiesta di cancellazione già inviata.');
        }

        $scheduledAt = now()->addDays(self::DELETION_GRACE_DAYS);

        $user->forceFill([
            'deletion_requested_at'  => now(),
            'deletion_reason'      => trim($reason),
            'deletion_scheduled_at'=> $scheduledAt,
            'active'               => false,
        ])->save();

        $this->audit->record(
            'settings',
            'Richiesta cancellazione account GDPR',
            $user,
            [
                'deletion_scheduled_at' => $scheduledAt->toIso8601String(),
                'reason_length'         => strlen(trim($reason)),
            ],
            $user->id,
        );

        $this->notifications->dispatch(
            NotificationEvent::GdprDeletionRequested,
            new GdprDeletionRequestAdminMail($user, trim($reason), $scheduledAt),
        );
    }

    /** Soft-delete accounts whose 30-day grace period has elapsed. */
    public function processScheduledDeletions(): int
    {
        $due = User::query()
            ->whereNotNull('deletion_scheduled_at')
            ->whereNull('deleted_at')
            ->where('deletion_scheduled_at', '<=', Carbon::now())
            ->get();

        foreach ($due as $user) {
            $this->audit->record(
                'settings',
                'Account eliminato (GDPR grace period)',
                $user,
                ['user_email' => $user->email],
            );

            $user->delete();
        }

        return $due->count();
    }
}
