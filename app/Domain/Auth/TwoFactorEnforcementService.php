<?php

namespace App\Domain\Auth;

use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonInterface;

class TwoFactorEnforcementService
{
    public function isEnforcementEnabled(): bool
    {
        return (bool) config('two-factor.enforce_admin_segreteria', false);
    }

    public function isWithinGracePeriod(?CarbonInterface $now = null): bool
    {
        $until = $this->graceUntil();

        if ($until === null) {
            return false;
        }

        $now ??= now();

        return $now->lessThan($until);
    }

    public function appliesTo(User $user): bool
    {
        if (! $this->isEnforcementEnabled()) {
            return false;
        }

        return $user->hasAnyRole(['admin', 'segreteria']);
    }

    public function requiresTwoFactorSetup(User $user): bool
    {
        return $this->appliesTo($user) && ! $user->hasTwoFactorEnabled();
    }

    public function isBlocked(User $user): bool
    {
        return $this->requiresTwoFactorSetup($user) && ! $this->isWithinGracePeriod();
    }

    public function shouldShowGraceBanner(User $user): bool
    {
        return $this->requiresTwoFactorSetup($user) && $this->isWithinGracePeriod();
    }

    public function graceUntilLabel(): ?string
    {
        $until = $this->graceUntil();

        return $until?->timezone(config('app.timezone'))->format('d/m/Y H:i');
    }

    public function redirectMessage(): string
    {
        return 'Per accedere all\'area segreteria è obbligatorio attivare l\'autenticazione a due fattori. Configurala in questa pagina.';
    }

    public function graceBannerMessage(): string
    {
        $until = $this->graceUntilLabel() ?? '—';

        return sprintf(
            'Attenzione: entro il %s l\'accesso all\'area segreteria richiederà l\'autenticazione a due fattori (TOTP). Attivala ora in Impostazioni → Sicurezza.',
            $until,
        );
    }

    private function graceUntil(): ?CarbonInterface
    {
        $raw = config('two-factor.enforce_grace_until');

        if (blank($raw)) {
            return null;
        }

        try {
            return Carbon::parse((string) $raw);
        } catch (\Throwable) {
            return null;
        }
    }
}
