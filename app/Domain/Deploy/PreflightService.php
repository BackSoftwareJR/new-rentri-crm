<?php

namespace App\Domain\Deploy;

use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;
use Illuminate\Support\Facades\DB;

class PreflightService
{
    public function __construct(
        private readonly RentriRuntimeModeService $runtimeMode,
    ) {}

    /**
     * @return array{
     *   passed: bool,
     *   checks: list<array{name: string, status: string, message: string}>
     * }
     */
    public function run(?string $manifestPath = null): array
    {
        // App key must be present first — subsequent checks (session, encryption) depend on it.
        $appKeyCheck = $this->checkAppKey();
        if ($appKeyCheck['status'] === 'fail') {
            return [
                'passed' => false,
                'checks' => [$appKeyCheck],
            ];
        }

        $checks = [
            $appKeyCheck,
            $this->checkProductionDebug(),
            $this->checkDatabase(),
            $this->checkViteManifest($manifestPath ?? public_path('build/manifest.json')),
            $this->checkRentriCertificate(),
            $this->checkRentriFirmaCertificate(),
            $this->checkRentriOperatorData(),
            $this->checkRentriStubMode(),
            $this->checkRentriFirmaStubMode(),
            $this->checkRentriSandboxCertPath(),
        ];

        $failed = collect($checks)->contains(fn (array $c) => $c['status'] === 'fail');

        return [
            'passed' => ! $failed,
            'checks' => $checks,
        ];
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkAppKey(): array
    {
        $key = (string) config('app.key');

        if ($key === '') {
            return $this->result('app_key', 'fail', 'APP_KEY non impostata.');
        }

        return $this->result('app_key', 'ok', 'APP_KEY configurata.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkProductionDebug(): array
    {
        if (config('app.env') === 'production' && config('app.debug') === true) {
            return $this->result('app_debug', 'fail', 'APP_DEBUG=true in ambiente production.');
        }

        if (config('app.debug') === true) {
            return $this->result('app_debug', 'warn', 'APP_DEBUG=true (accettabile solo in staging/dev).');
        }

        return $this->result('app_debug', 'ok', 'APP_DEBUG disabilitato.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDatabase(): array
    {
        try {
            DB::connection()->getPdo();

            return $this->result('database', 'ok', 'Connessione database OK ('.config('database.default').').');
        } catch (\Throwable $e) {
            return $this->result('database', 'fail', 'Database non raggiungibile: '.$e->getMessage());
        }
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkViteManifest(string $path): array
    {
        if (! is_readable($path)) {
            return $this->result('vite_manifest', 'fail', 'Manifest Vite assente: eseguire npm run build.');
        }

        return $this->result('vite_manifest', 'ok', 'Manifest Vite presente.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriCertificate(): array
    {
        $settings = RentriSetting::instance();

        if ($this->runtimeMode->isApiStub($settings)) {
            return $this->result('rentri_cert', 'warn', 'API RENTRI in stub — certificato DB non richiesto.');
        }

        if (blank($settings->cert_path_encrypted)) {
            return $this->result('rentri_cert', 'fail', 'Certificato RENTRI non configurato (wizard o RENTRI_CERT_PATH).');
        }

        if ($settings->cert_scadenza !== null && $settings->cert_scadenza->isPast()) {
            return $this->result('rentri_cert', 'fail', 'Certificato RENTRI scaduto.');
        }

        return $this->result('rentri_cert', 'ok', 'Certificato interoperabilità RENTRI configurato.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriFirmaCertificate(): array
    {
        $settings = RentriSetting::instance();

        if ($this->runtimeMode->isFirmaStub($settings)) {
            return $this->result('rentri_firma_cert', 'warn', 'Firma xFIR in stub — certificato firma non richiesto.');
        }

        if (blank($settings->firma_cert_path_encrypted)) {
            return $this->result('rentri_firma_cert', 'fail', 'Certificato firma xFIR non configurato (impostazioni RENTRI).');
        }

        if ($settings->firma_cert_scadenza !== null && $settings->firma_cert_scadenza->isPast()) {
            return $this->result('rentri_firma_cert', 'fail', 'Certificato firma xFIR scaduto.');
        }

        return $this->result('rentri_firma_cert', 'ok', 'Certificato firma xFIR configurato.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriOperatorData(): array
    {
        $settings = RentriSetting::instance();

        if ($this->runtimeMode->isApiStub($settings)) {
            return $this->result('rentri_operator', 'warn', 'API RENTRI in stub — dati operatore non verificati.');
        }

        if (blank($settings->num_iscr_sito) || blank($settings->cf_operatore)) {
            return $this->result('rentri_operator', 'fail', 'Dati operatore incompleti (num_iscr_sito, cf_operatore).');
        }

        if ($settings->onboarding_step_completed < 3) {
            return $this->result('rentri_operator', 'fail', 'Onboarding RENTRI incompleto — eseguire test connessione.');
        }

        return $this->result('rentri_operator', 'ok', 'Dati operatore e onboarding RENTRI completi.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriFirmaStubMode(): array
    {
        $settings = RentriSetting::instance();
        $firmaStub = $this->runtimeMode->isFirmaStub($settings);

        if (config('app.env') === 'production' && $firmaStub) {
            return $this->result('rentri_firma_stub', 'warn', 'Firma xFIR in stub in production — abilitare firma live.');
        }

        if ($firmaStub) {
            return $this->result('rentri_firma_stub', 'ok', 'Modalità firma stub (dev/staging).');
        }

        return $this->result('rentri_firma_stub', 'ok', 'Firma xFIR live attiva.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriStubMode(): array
    {
        $settings = RentriSetting::instance();
        $apiStub = $this->runtimeMode->isApiStub($settings);

        if (config('app.env') === 'production' && $apiStub) {
            return $this->result('rentri_stub', 'warn', 'API RENTRI in stub in production — abilitare passaggio live.');
        }

        if ($apiStub) {
            return $this->result('rentri_stub', 'ok', 'Modalità stub RENTRI (dev/staging).');
        }

        if ($settings->live_mode_enabled_at !== null) {
            return $this->result('rentri_stub', 'ok', 'Modalità live attiva (override runtime UI).');
        }

        return $this->result('rentri_stub', 'ok', 'RENTRI_API_STUB disabilitato (live).');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriSandboxCertPath(): array
    {
        $path = config('services.rentri.sandbox_cert_path');

        if (blank($path)) {
            return $this->result('rentri_sandbox_cert', 'ok', 'RENTRI_SANDBOX_CERT_PATH non configurato (opzionale).');
        }

        if (! is_readable($path)) {
            return $this->result('rentri_sandbox_cert', 'fail', 'RENTRI_SANDBOX_CERT_PATH non leggibile: '.$path);
        }

        return $this->result('rentri_sandbox_cert', 'ok', 'Certificato sandbox file path leggibile.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function result(string $name, string $status, string $message): array
    {
        return [
            'name'    => $name,
            'status'  => $status,
            'message' => $message,
        ];
    }
}
