<?php

namespace App\Http\Livewire\Settings;

use App\Domain\Auth\TwoFactorService;
use App\Domain\Gdpr\GdprService;
use App\Http\Livewire\Segreteria\SegreteriaPage;
use App\Rules\StrongPassword;
use App\Support\TwoFactorSettings;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Title;
use Symfony\Component\HttpFoundation\StreamedResponse;

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

    // Recovery codes displayed once after enabling (or regenerating)
    /** @var list<string> */
    public array $newRecoveryCodes = [];

    public bool $showRecoveryModal = false;

    public bool $recoveryCodesAcknowledged = false;

    // Regeneration flow
    public bool $showRegenForm = false;

    public string $regenPassword = '';

    // Password change
    public string $currentPassword = '';

    public string $newPassword = '';

    public string $newPasswordConfirmation = '';

    // GDPR deletion request
    public bool $showDeletionModal = false;

    public string $deletionReason = '';

    public string $deletionConfirmText = '';

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

        $plainCodes = $twoFactor->enable(auth()->user(), $this->setupSecret);

        $this->resetSetupState();
        $this->enabled = true;
        $this->newRecoveryCodes = $plainCodes;
        $this->showRecoveryModal = true;
        $this->recoveryCodesAcknowledged = false;
    }

    public function acknowledgeRecoveryCodes(): void
    {
        if (! $this->recoveryCodesAcknowledged) {
            $this->addError('recoveryCodesAcknowledged', 'Conferma di aver salvato i codici prima di continuare.');

            return;
        }

        $this->closeRecoveryModal();

        session()->flash('success', 'Autenticazione a due fattori attivata. Conserva i codici di recupero in un luogo sicuro.');
    }

    public function closeRecoveryModal(): void
    {
        $this->showRecoveryModal = false;
        $this->newRecoveryCodes = [];
        $this->recoveryCodesAcknowledged = false;
        $this->resetErrorBag('recoveryCodesAcknowledged');
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
        $this->showRegenForm = false;
        $this->regenPassword = '';

        session()->flash('success', 'Autenticazione a due fattori disattivata.');
    }

    public function toggleRegenForm(): void
    {
        $this->showRegenForm = ! $this->showRegenForm;
        $this->regenPassword = '';
        $this->resetErrorBag('regenPassword');
    }

    public function regenerateRecoveryCodes(TwoFactorService $twoFactor): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $this->validate([
            'regenPassword' => ['required', 'string'],
        ]);

        if (! Hash::check($this->regenPassword, auth()->user()->password)) {
            $this->addError('regenPassword', 'Password non corretta.');

            return;
        }

        $plainCodes = $twoFactor->regenerateRecoveryCodes(auth()->user());

        $this->showRegenForm = false;
        $this->regenPassword = '';
        $this->newRecoveryCodes = $plainCodes;
        $this->showRecoveryModal = true;
        $this->recoveryCodesAcknowledged = false;
    }

    public function changePassword(): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $this->validate([
            'currentPassword'         => ['required', 'string'],
            'newPassword'             => ['required', 'string', 'confirmed', new StrongPassword],
            'newPasswordConfirmation' => ['required', 'string'],
        ]);

        $user = auth()->user();

        if (! Hash::check($this->currentPassword, $user->password)) {
            $this->addError('currentPassword', 'Password attuale non corretta.');

            return;
        }

        $user->password = Hash::make($this->newPassword);
        $user->save();

        $this->currentPassword = '';
        $this->newPassword = '';
        $this->newPasswordConfirmation = '';

        session()->flash('success', 'Password aggiornata con successo.');
    }

    public function downloadMyData(GdprService $gdpr): StreamedResponse
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $user = auth()->user();
        $payload = $gdpr->exportUserData($user);
        $filename = 'dati-personali-'.$user->id.'-'.now()->format('Y-m-d').'.json';

        return response()->streamDownload(
            static fn () => print(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
            $filename,
            ['Content-Type' => 'application/json; charset=utf-8'],
        );
    }

    public function openDeletionModal(): void
    {
        $this->showDeletionModal = true;
        $this->deletionReason = '';
        $this->deletionConfirmText = '';
        $this->resetErrorBag();
    }

    public function closeDeletionModal(): void
    {
        $this->showDeletionModal = false;
        $this->deletionReason = '';
        $this->deletionConfirmText = '';
    }

    public function requestAccountDeletion(GdprService $gdpr): void
    {
        $this->authorize('manage', TwoFactorSettings::instance());

        $this->validate([
            'deletionReason'      => ['required', 'string', 'min:10', 'max:2000'],
            'deletionConfirmText' => ['required', 'in:ELIMINA'],
        ], [
            'deletionReason.required' => 'Indica il motivo della richiesta.',
            'deletionReason.min'      => 'Il motivo deve contenere almeno 10 caratteri.',
            'deletionConfirmText.in'  => 'Digita ELIMINA per confermare.',
        ]);

        $user = auth()->user();

        try {
            $gdpr->requestDeletion($user, $this->deletionReason);
        } catch (\InvalidArgumentException $e) {
            $this->addError('deletionReason', $e->getMessage());

            return;
        }

        $this->closeDeletionModal();

        auth()->logout();
        session()->invalidate();
        session()->regenerateToken();

        session()->flash('success', 'Richiesta di cancellazione inviata. L\'account verrà eliminato entro 30 giorni.');
        $this->redirectRoute('login');
    }

    public function render(TwoFactorService $twoFactor): View
    {
        $remainingCodes = $this->enabled
            ? $twoFactor->remainingRecoveryCodesCount(auth()->user())
            : 0;

        return $this->segreteriaView(
            'livewire.settings.security-settings',
            [
                'issuer'         => config('two-factor.issuer'),
                'remainingCodes' => $remainingCodes,
                'deletionPending'=> auth()->user()->deletion_requested_at !== null,
                'deletionScheduledAt' => auth()->user()->deletion_scheduled_at,
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
