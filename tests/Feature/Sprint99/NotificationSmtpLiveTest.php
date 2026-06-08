<?php

namespace Tests\Feature\Sprint99;

use App\Domain\Notifications\MailTransportRuntimeService;
use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Settings\NotificationSettingsPage;
use App\Mail\NotificationTestMail;
use App\Mail\RentriDeadLetterMail;
use App\Models\User;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;
use Tests\TestCase;

class NotificationSmtpLiveTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Config::set('notifications.live', false);
    }

    public function test_mail_runtime_defaults_to_stub(): void
    {
        $runtime = app(MailTransportRuntimeService::class);

        $this->assertFalse($runtime->isLive());
        $this->assertSame('stub', $runtime->modeLabel());
        $this->assertSame('Notifiche stub (log)', $runtime->modeDisplayLabel());
        $this->assertSame('log', $runtime->effectiveMailerName());
    }

    public function test_stub_mode_deliver_does_not_send_mail(): void
    {
        Mail::fake();

        app(NotificationService::class)->deliver(
            new RentriDeadLetterMail(1, 'Timeout'),
            'ops@example.it',
            ['event' => 'rentri.dead_letter', 'module' => 'rentri'],
        );

        Mail::assertNothingSent();
    }

    public function test_live_mode_deliver_sends_via_default_mailer(): void
    {
        Config::set('notifications.live', true);
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.from.address', 'noreply@rentri-crm.test');

        Mail::fake();

        app(NotificationService::class)->deliver(
            new RentriDeadLetterMail(9, 'Errore firma'),
            'ops@example.it',
            ['event' => NotificationEvent::RentriDeadLetter->value, 'module' => 'rentri'],
        );

        Mail::assertSent(RentriDeadLetterMail::class, function (RentriDeadLetterMail $mail) {
            return $mail->hasTo('ops@example.it');
        });
    }

    public function test_live_preflight_requires_smtp_configuration(): void
    {
        Config::set('notifications.live', true);
        Config::set('mail.default', 'log');
        Config::set('mail.from.address', 'hello@example.com');
        Config::set('mail.mailers.log.host', '127.0.0.1');

        $runtime = app(MailTransportRuntimeService::class);

        $this->assertTrue($runtime->isLive());
        $this->assertFalse($runtime->preflightReady());
        $this->assertGreaterThanOrEqual(2, count($runtime->preflightChecklist()));
    }

    public function test_send_test_email_live_sends_notification_test_mailable(): void
    {
        Config::set('notifications.live', true);
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.from.address', 'noreply@rentri-crm.test');

        Mail::fake();

        app(NotificationService::class)->sendTestEmail('qa@example.it', 'segreteria@example.com');

        Mail::assertSent(NotificationTestMail::class, function (NotificationTestMail $mail) {
            return $mail->hasTo('qa@example.it')
                && $mail->sentBy === 'segreteria@example.com';
        });
    }

    public function test_notification_settings_livewire_sends_test_email_in_live_mode(): void
    {
        Config::set('notifications.live', true);
        Config::set('mail.default', 'smtp');
        Config::set('mail.mailers.smtp.host', 'smtp.example.com');
        Config::set('mail.from.address', 'noreply@rentri-crm.test');

        Mail::fake();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationSettingsPage::class)
            ->set('testRecipient', 'qa@example.it')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertSee('seg-alert-success')
            ->assertSee('Email di test inviata');

        Mail::assertSent(NotificationTestMail::class);
    }

    public function test_notification_settings_livewire_stub_test_email_logs_only(): void
    {
        Mail::fake();

        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        Livewire::actingAs($user)
            ->test(NotificationSettingsPage::class)
            ->set('testRecipient', 'qa@example.it')
            ->call('sendTestEmail')
            ->assertHasNoErrors()
            ->assertSee('seg-alert-success')
            ->assertSee('Test registrato in log');

        Mail::assertNothingSent();
    }

    public function test_notification_settings_page_shows_mail_mode_badge(): void
    {
        $user = User::where('email', 'segreteria@example.com')->firstOrFail();

        $this->actingAs($user)
            ->get(route('segreteria.impostazioni.notifiche'))
            ->assertOk()
            ->assertSee('Notifiche stub (log)')
            ->assertSee('Invia email di test');
    }
}
