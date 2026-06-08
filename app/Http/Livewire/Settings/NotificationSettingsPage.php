<?php

namespace App\Http\Livewire\Settings;

use App\Domain\Infrastructure\HorizonScalingPreflightService;
use App\Domain\Notifications\MailTransportRuntimeService;
use App\Domain\Notifications\NotificationPreferenceService;
use App\Domain\Notifications\NotificationService;
use App\Domain\Notifications\SmtpVolumePreflightService;
use App\Enums\NotificationEvent;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Support\NotificationSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Impostazioni notifiche')]
class NotificationSettingsPage extends SegreteriaPage
{
    use AuthorizesRequests;

    /** @var array<string, bool> */
    public array $toggles = [];

    public string $testRecipient = '';

    public function mount(NotificationPreferenceService $preferences): void
    {
        $settings = NotificationSettings::instance();
        $this->authorize('view', $settings);

        $this->testRecipient = (string) config('notifications.default_recipient');

        foreach (NotificationEvent::all() as $event) {
            $this->toggles[$this->toggleKey($event)] = $preferences->isEnabled($event);
        }
    }

    public function save(NotificationPreferenceService $preferences): void
    {
        $settings = NotificationSettings::instance();
        $this->authorize('update', $settings);

        $validated = [];
        foreach (NotificationEvent::all() as $event) {
            $validated[$event->value] = (bool) ($this->toggles[$this->toggleKey($event)] ?? false);
        }

        $preferences->save($validated);

        foreach (NotificationEvent::all() as $event) {
            $this->toggles[$this->toggleKey($event)] = $preferences->isEnabled($event);
        }

        session()->flash('success', 'Preferenze notifiche salvate.');
    }

    public function sendTestEmail(
        NotificationService $notifications,
        MailTransportRuntimeService $mailRuntime,
    ): void {
        $settings = NotificationSettings::instance();
        $this->authorize('update', $settings);

        $this->validate([
            'testRecipient' => ['required', 'email'],
        ]);

        if ($mailRuntime->isLive() && ! $mailRuntime->preflightReady()) {
            session()->flash('error', 'SMTP non configurato: completare la checklist MAIL_* prima dell\'invio live.');

            return;
        }

        $sentBy = auth()->user()?->email ?? 'sistema';

        $notifications->sendTestEmail($this->testRecipient, $sentBy);

        if ($mailRuntime->isLive()) {
            session()->flash('success', 'Email di test inviata a '.$this->testRecipient.'.');
        } else {
            session()->flash('success', 'Test registrato in log (modalità stub — nessun SMTP).');
        }
    }

    private function toggleKey(NotificationEvent $event): string
    {
        return str_replace('.', '__', $event->value);
    }

    public function render(
        HorizonScalingPreflightService $horizonPreflight,
        SmtpVolumePreflightService $smtpVolume,
    ): View {
        $mailRuntime = app(MailTransportRuntimeService::class);

        return $this->segreteriaView(
            'livewire.settings.notification-settings',
            [
                'events'              => NotificationEvent::all(),
                'queued'              => (bool) config('notifications.queue'),
                'mailRuntime'         => $mailRuntime,
                'mailPreflight'       => $mailRuntime->preflightChecklist(),
                'mailPreflightOk'     => $mailRuntime->preflightReady(),
                'horizonChecklist'    => $horizonPreflight->checklist(),
                'horizonSummary'      => $horizonPreflight->summary(),
                'horizonReady'        => $horizonPreflight->isReadyForProductionVolume(),
                'smtpVolumeChecklist' => $smtpVolume->checklist(),
                'smtpVolumeSummary'   => $smtpVolume->summary(),
                'smtpVolumeReady'     => $smtpVolume->isReadyForProductionVolume(),
            ],
            'impostazioni-notifiche',
            'Impostazioni notifiche',
        );
    }
}
