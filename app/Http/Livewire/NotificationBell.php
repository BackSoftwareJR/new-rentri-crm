<?php

namespace App\Http\Livewire;

use App\Domain\Notifications\NotificationService;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * In-app notification bell component.
 *
 * Include in any authenticated layout with:
 *   <livewire:notification-bell />
 *
 * Polls every 60 s to refresh the unread count without full page reload.
 */
class NotificationBell extends Component
{
    public bool $open = false;

    public int $unreadCount = 0;

    public function mount(): void
    {
        $this->refresh();
    }

    public function toggle(): void
    {
        $this->open = ! $this->open;

        if ($this->open) {
            $this->refresh();
        }
    }

    public function markAllRead(NotificationService $service): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $service->markAllReadForUser($user);
        $this->unreadCount = 0;
        $this->dispatch('notifications-cleared');
    }

    public function markOneRead(string $notificationId): void
    {
        /** @var User $user */
        $user = auth()->user();

        if (! $user) {
            return;
        }

        $notification = $user->notifications()->where('id', $notificationId)->first();
        $notification?->markAsRead();

        $this->refresh();
    }

    #[On('refresh-notifications')]
    public function refresh(): void
    {
        /** @var User|null $user */
        $user = auth()->user();

        $this->unreadCount = $user
            ? app(NotificationService::class)->unreadCountForUser($user)
            : 0;
    }

    public function render(NotificationService $service): View
    {
        /** @var User|null $user */
        $user = auth()->user();

        $notifications = $user
            ? $service->allForUser($user, 10)
            : collect();

        $this->unreadCount = $user
            ? $service->unreadCountForUser($user)
            : 0;

        return view('livewire.notification-bell', compact('notifications'));
    }
}
