<?php

namespace Tests\Feature\Sprint66;

use App\Domain\Notifications\NotificationPreferenceService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Settings\NotificationSettingsPage;
use App\Jobs\SendNotificationJob;
use App\Mail\RentriDeadLetterMail;
use App\Mail\SerbatoioSogliaAlertMail;
use App\Models\User;
use App\Support\NotificationSettings;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Queue;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationHubTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        app(NotificationPreferenceService::class)->reset();
    }

    public function test_notification_service_dispatches_to_log_channel(): void
    {
        $result = app(NotificationService::class)->dispatch(
            NotificationEvent::RentriDeadLetter,
            new RentriDeadLetterMail(42, 'Timeout gateway', 'E504'),
            context: ['transazione_id' => 42],
        );

        $this->assertTrue($result);
    }

    public function test_disabled_event_skips_dispatch(): void
    {
        $preferences = app(NotificationPreferenceService::class);
        $preferences->setEnabled(NotificationEvent::MagazzinoSerbatoioSoglia, false);

        $sent = app(NotificationService::class)->dispatch(
            NotificationEvent::MagazzinoSerbatoioSoglia,
            new SerbatoioSogliaAlertMail(['codice' => 'X', 'stato' => 'attenzione'], 'Attenzione'),
        );

        $this->assertFalse($sent);
    }

    public function test_queue_mode_dispatches_job(): void
    {
        config(['notifications.queue' => true]);

        Queue::fake();

        app(NotificationService::class)->dispatch(
            NotificationEvent::RentriDeadLetter,
            new RentriDeadLetterMail(7, 'Errore firma'),
        );

        Queue::assertPushed(SendNotificationJob::class, function (SendNotificationJob $job): bool {
            return $job->event === NotificationEvent::RentriDeadLetter
                && $job->recipient === config('notifications.default_recipient');
        });
    }

    public function test_serbatoio_mail_template_renders_stub_content(): void
    {
        $mail = new SerbatoioSogliaAlertMail([
            'codice'              => '16.01.96*',
            'stato'               => 'superata',
            'percentuale'         => 110.0,
            'quantita_attuale_kg' => 1100,
            'limite_kg'           => 1000,
        ], 'Soglia superata');

        $html = $mail->render();
        $this->assertStringContainsString('16.01.96*', $html);
        $this->assertStringContainsString('Soglia superata', $html);
        $this->assertStringContainsString('Alert soglia serbatoio', $html);
    }

    public function test_notification_settings_livewire_saves_toggles(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationSettingsPage::class)
            ->set('toggles.mud__invio_telematico', false)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertFalse(
            app(NotificationPreferenceService::class)->isEnabled(NotificationEvent::MudInvioTelematico),
        );
    }

    public function test_operatore_cannot_update_notification_settings(): void
    {
        $user = User::where('email', 'operatore@example.com')->firstOrFail();
        $settings = NotificationSettings::instance();

        $this->assertFalse(Gate::forUser($user)->allows('update', $settings));
    }

    public function test_notification_settings_route_accessible_to_segreteria(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.notifiche'))
            ->assertOk();
    }
}
