<?php

namespace App\Domain\Mud;

use App\Support\Demo\DemoContext;

class MudTelematicoRuntimeModeService
{
    public function isStub(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        return (bool) config('services.mud_telematico.stub', true);
    }

    public function modeLabel(): string
    {
        return $this->isStub() ? 'stub' : 'live';
    }

    public function modeDisplayLabel(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'MUD demo offline',
            'stub'    => 'MUD stub',
            default   => 'MUD telematico live',
        };
    }

    public function modeDisplayVariant(): string
    {
        return match ($this->modeKind()) {
            'offline' => 'warning',
            'stub'    => 'info',
            default   => 'success',
        };
    }

    /**
     * @return 'offline'|'stub'|'live'
     */
    public function modeKind(): string
    {
        if (DemoContext::offlineNoHttp()) {
            return 'offline';
        }

        return $this->isStub() ? 'stub' : 'live';
    }

    public function invioButtonLabel(): string
    {
        return $this->isStub()
            ? 'Invia telematico (stub)'
            : 'Invia telematico MASE';
    }

    public function invioConfirmMessage(): string
    {
        return $this->isStub()
            ? 'Confermi l\'invio telematico stub al ministero?'
            : 'Confermi l\'invio telematico MASE (sandbox/live)? L\'operazione è irreversibile.';
    }
}
