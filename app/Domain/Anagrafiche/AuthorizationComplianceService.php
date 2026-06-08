<?php

namespace App\Domain\Anagrafiche;

use App\Models\Anagrafica;
use App\Models\Authorization;
use Carbon\Carbon;

class AuthorizationComplianceService
{
    public const EXPIRY_WARNING_DAYS = 15;

    public function requiresAuthorization(Anagrafica $anagrafica): bool
    {
        if ($anagrafica->tipo === 'trasportatore') {
            return true;
        }

        return $anagrafica->tipo === 'impianto' && $anagrafica->gestisce_trasporti;
    }

    public function hasValidAuthorization(Anagrafica $anagrafica): bool
    {
        if (! $this->requiresAuthorization($anagrafica)) {
            return true;
        }

        return $anagrafica->authorizations
            ->contains(fn (Authorization $auth) => $this->authorizationIsValid($auth));
    }

    public function authorizationIsValid(Authorization $authorization): bool
    {
        if ($authorization->scade_il === null) {
            return true;
        }

        return $authorization->scade_il->endOfDay()->gte(now());
    }

    public function daysUntilExpiry(?Authorization $authorization): ?int
    {
        if ($authorization?->scade_il === null) {
            return null;
        }

        return (int) now()->startOfDay()->diffInDays($authorization->scade_il, false);
    }

    public function authorizationStatus(Authorization $authorization): string
    {
        if ($authorization->scade_il === null) {
            return 'valida';
        }

        $days = $this->daysUntilExpiry($authorization);

        if ($days < 0) {
            return 'scaduta';
        }

        if ($days <= self::EXPIRY_WARNING_DAYS) {
            return 'in_scadenza';
        }

        return 'valida';
    }

    public function anagraficaComplianceStatus(Anagrafica $anagrafica): string
    {
        if (! $this->requiresAuthorization($anagrafica)) {
            return 'non_richiesta';
        }

        if ($this->hasValidAuthorization($anagrafica)) {
            $soonest = $this->soonestExpiringAuthorization($anagrafica);

            if ($soonest && $this->authorizationStatus($soonest) === 'in_scadenza') {
                return 'in_scadenza';
            }

            return 'valida';
        }

        return 'non_conforme';
    }

    public function soonestExpiringAuthorization(Anagrafica $anagrafica): ?Authorization
    {
        return $anagrafica->authorizations
            ->filter(fn (Authorization $auth) => $auth->scade_il !== null)
            ->sortBy('scade_il')
            ->first();
    }

    public function isExpiringWithinWarning(Authorization $authorization): bool
    {
        $days = $this->daysUntilExpiry($authorization);

        return $days !== null && $days >= 0 && $days < self::EXPIRY_WARNING_DAYS;
    }
}
