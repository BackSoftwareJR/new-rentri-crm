<?php

namespace App\Domain\Fatturazione;

use App\Support\Demo\DemoContext;

class SdiRuntimeModeService
{
    public function isStub(): bool
    {
        if (DemoContext::offlineNoHttp()) {
            return true;
        }

        return (bool) config('services.sdi.stub', true);
    }

    public function modeLabel(): string
    {
        return $this->isStub() ? 'stub' : 'live';
    }

    public function invioButtonLabel(): string
    {
        return $this->isStub()
            ? 'Invia a SDI (stub)'
            : 'Invia a SDI';
    }

    public function invioConfirmMessage(): string
    {
        return $this->isStub()
            ? 'Confermi l\'invio stub verso SDI? Nessuna trasmissione reale.'
            : 'Confermi l\'invio telematico verso SDI? L\'operazione è irreversibile.';
    }
}
