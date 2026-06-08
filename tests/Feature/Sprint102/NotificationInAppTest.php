<?php

namespace Tests\Feature\Sprint102;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\NotificationBell;
use App\Models\User;
use App\Notifications\AppNotification;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationInAppTest extends TestCase
{
    // -------------------------------------------------------------------------
    // AppNotification class
    // -------------------------------------------------------------------------

    public function test_app_notification_uses_database_channel(): void
    {
        $notification = new AppNotification(
            NotificationEvent::BonificaPericolosiCompletata,
            'Test title',
        );

        $this->assertSame(['database'], $notification->via(new User));
    }

    public function test_app_notification_to_database_payload(): void
    {
        $notification = new AppNotification(
            NotificationEvent::BonificaPericolosiCompletata,
            'Bonifica completata',
            'Il veicolo XY123 ha completato la fase pericolosi.',
            '/segreteria/vfu/1',
            ['vfu_id' => 1],
        );

        $payload = $notification->toDatabase(new User);

        $this->assertSame('bonifica.pericolosi_completata', $payload['event']);
        $this->assertSame('bonifica', $payload['module']);
        $this->assertSame('Bonifica completata', $payload['title']);
        $this->assertSame('Il veicolo XY123 ha completato la fase pericolosi.', $payload['body']);
        $this->assertSame('/segreteria/vfu/1', $payload['url']);
    }

    // -------------------------------------------------------------------------
    // NotificationService.notifyInApp
    // -------------------------------------------------------------------------

    public function test_notify_in_app_stores_database_notification_for_user(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        app(NotificationService::class)->notifyInApp(
            NotificationEvent::BonificaPericolosiCompletata,
            'Test notifica in-app',
            $user,
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $user->id,
            'type' => AppNotification::class,
        ]);
    }

    public function test_notify_in_app_sends_to_all_admin_segreteria_when_no_user(): void
    {
        $admin = User::where('email', 'admin@example.com')->firstOrFail();
        $segreteria = User::where('email', 'segreteria@example.com')->firstOrFail();

        app(NotificationService::class)->notifyInApp(
            NotificationEvent::MagazzinoSerbatoioSoglia,
            'Alert soglia',
        );

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $admin->id,
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $segreteria->id,
        ]);
    }

    // -------------------------------------------------------------------------
    // unreadCount and markAllRead
    // -------------------------------------------------------------------------

    public function test_unread_count_increments_on_notify(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(NotificationService::class);

        $this->assertSame(0, $service->unreadCountForUser($user));

        $service->notifyInApp(NotificationEvent::BonificaPericolosiCompletata, 'Test 1', $user);
        $service->notifyInApp(NotificationEvent::MagazzinoSerbatoioSoglia, 'Test 2', $user);

        $this->assertSame(2, $service->unreadCountForUser($user));
    }

    public function test_mark_all_read_clears_unread_count(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(NotificationService::class);

        $service->notifyInApp(NotificationEvent::BonificaPericolosiCompletata, 'Test 1', $user);
        $service->notifyInApp(NotificationEvent::MagazzinoSerbatoioSoglia, 'Test 2', $user);

        $service->markAllReadForUser($user);

        $this->assertSame(0, $service->unreadCountForUser($user));
        $this->assertSame(2, $service->allForUser($user, 10)->count());
    }

    // -------------------------------------------------------------------------
    // NotificationBell Livewire component
    // -------------------------------------------------------------------------

    public function test_notification_bell_renders_for_authenticated_user(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 0)
            ->assertSee('Notifiche');
    }

    public function test_notification_bell_shows_unread_count(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        app(NotificationService::class)->notifyInApp(
            NotificationEvent::BonificaPericolosiCompletata,
            'Bonifica targa TEST00',
            $user,
        );

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 1);
    }

    public function test_notification_bell_mark_all_read(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(NotificationService::class);

        $service->notifyInApp(NotificationEvent::BonificaPericolosiCompletata, 'Notifica 1', $user);
        $service->notifyInApp(NotificationEvent::MagazzinoSerbatoioSoglia, 'Notifica 2', $user);

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('unreadCount', 2)
            ->call('markAllRead')
            ->assertSet('unreadCount', 0);

        $this->assertSame(0, $service->unreadCountForUser($user));
    }

    public function test_notification_bell_toggle_opens_and_closes(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->assertSet('open', false)
            ->call('toggle')
            ->assertSet('open', true)
            ->call('toggle')
            ->assertSet('open', false);
    }

    public function test_mark_one_read_marks_single_notification(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();
        $service = app(NotificationService::class);

        $service->notifyInApp(NotificationEvent::BonificaPericolosiCompletata, 'Prima notifica', $user);
        $service->notifyInApp(NotificationEvent::MagazzinoSerbatoioSoglia, 'Seconda notifica', $user);

        $this->assertSame(2, $service->unreadCountForUser($user));

        $notificationId = $user->unreadNotifications()->first()->id;

        Livewire::actingAs($user)
            ->test(NotificationBell::class)
            ->call('markOneRead', $notificationId)
            ->assertSet('unreadCount', 1);
    }
}
