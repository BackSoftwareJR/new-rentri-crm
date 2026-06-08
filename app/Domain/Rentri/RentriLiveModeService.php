<?php

namespace App\Domain\Rentri;

use App\Domain\Audit\ActivityLogService;
use App\Models\RentriSetting;
use Illuminate\Validation\ValidationException;

class RentriLiveModeService
{
    public function __construct(
        private readonly RentriProdReadinessService $readiness,
        private readonly ActivityLogService $audit,
    ) {}

    public function enable(RentriSetting $settings, int $userId): RentriSetting
    {
        if (! $this->readiness->canEnableLiveMode($settings)) {
            throw ValidationException::withMessages([
                'live' => 'Checklist pre-produzione non superata. Verificare certificati, health check e ambiente produzione.',
            ]);
        }

        if ($settings->live_mode_enabled_at !== null) {
            return $settings;
        }

        $settings->update([
            'live_mode_enabled_at'  => now(),
            'firma_live_enabled_at' => now(),
        ]);

        $fresh = $settings->fresh();

        $this->audit->record(
            'rentri',
            'Passaggio modalità live RENTRI (stub disabilitato via UI)',
            $fresh,
            [
                'ambiente'           => $fresh->ambiente,
                'live_mode_enabled'  => true,
                'checklist_summary'  => $this->readiness->summary($fresh),
            ],
            $userId,
        );

        return $fresh;
    }

    public function revertToStub(RentriSetting $settings, int $userId): RentriSetting
    {
        if ($settings->live_mode_enabled_at === null) {
            return $settings;
        }

        $settings->update([
            'live_mode_enabled_at'  => null,
            'firma_live_enabled_at' => null,
        ]);

        $fresh = $settings->fresh();

        $this->audit->record(
            'rentri',
            'Rientro modalità stub RENTRI (override UI disattivato)',
            $fresh,
            ['ambiente' => $fresh->ambiente],
            $userId,
        );

        return $fresh;
    }
}
