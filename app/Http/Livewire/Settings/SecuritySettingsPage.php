<?php

namespace App\Http\Livewire\Settings;

use App\Domain\Auth\TwoFactorService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Support\TwoFactorSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;

#[Title('Sicurezza account')]
class SecuritySettingsPage extends SegreteriaPage
{
    use AuthorizesRequests;

    public bool $enabled = false;

    public bool $setupMode = false;

    public ?string $setupSecret = null;

    public ?string $qrSvg = null;

    public string $confirmCode = '';

    public string $disableCode = '';

    public function mount(): void
    {
        $this->authorize('view', TwoFactorSettings::instance());

        $this->enabled = auth()->user()->hasTwoFactorEnabled();
    }

    public function startSetup(TwoFactorService $twoFactor): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        if ($this->enabled) {
            return;
        }

        $this->setupSecret = $twoFactor->generateSecret();
        $this->qrSvg = $twoFactor->qrCodeSvg(auth()->user(), $this->setupSecret);
        $this->setupMode = true;
        $this->confirmCode = '';
        $this->resetErrorBag();
    }

    public function confirmSetup(TwoFactorService $twoFactor): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $this->validate([
            'confirmCode' => ['required', 'string', 'size:6'],
        ]);

        if ($this->setupSecret === null) {
            $this->addError('confirmCode', 'Avvia prima la configurazione 2FA.');

            return;
        }

        if (! $twoFactor->verifySecret($this->setupSecret, $this->confirmCode)) {
            $this->addError('confirmCode', 'Codice non valido. Verifica l\'orologio del dispositivo.');

            return;
        }

        $twoFactor->enable(auth()->user(), $this->setupSecret);

        $this->resetSetupState();
        $this->enabled = true;

        session()->flash('success', 'Autenticazione a due fattori attivata.');
    }

    public function cancelSetup(): void
    {
        $this->resetSetupState();
    }

    public function disable(TwoFactorService $twoFactor): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $this->validate([
            'disableCode' => ['required', 'string', 'size:6'],
        ]);

        if (! $twoFactor->verifyUser(auth()->user(), $this->disableCode)) {
            $this->addError('disableCode', 'Codice non valido.');

            return;
        }

        $twoFactor->disable(auth()->user());

        $this->enabled = false;
        $this->disableCode = '';
        $this->resetSetupState();

        session()->flash('success', 'Autenticazione a due fattori disattivata.');
    }

    public function render(): View
    {
        return $this->segreteriaView(
            'livewire.settings.security-settings',
            [
                'issuer' => config('two-factor.issuer'),
            ],
            'impostazioni-sicurezza',
            'Sicurezza account',
        );
    }

    private function resetSetupState(): void
    {
        $this->setupMode = false;
        $this->setupSecret = null;
        $this->qrSvg = null;
        $this->confirmCode = '';
    }
}
