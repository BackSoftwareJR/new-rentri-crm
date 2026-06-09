<?php

namespace App\Domain\Deploy;

use App\Domain\Demo\DemoSeedService;
use App\Domain\Rentri\RentriRuntimeModeService;
use App\Models\RentriSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DemoPreflightService
{
    public function __construct(
        private readonly DemoSeedService $demoSeed,
        private readonly RentriRuntimeModeService $runtimeMode,
    ) {}

    /**
     * @return array{
     *   passed: bool,
     *   checks: list<array{name: string, status: string, message: string}>
     * }
     */
    public function run(?string $manifestPath = null, bool $requireSeed = false): array
    {
        $checks = [
            $this->checkAppKey(),
            $this->checkDemoModeEnabled(),
            $this->checkDemoEnvironment(),
            $this->checkDatabase(),
            $this->checkViteManifest($manifestPath ?? public_path('build/manifest.json')),
            $this->checkDemoForceSandbox(),
            $this->checkDemoSeed($requireSeed),
            $this->checkRentriStubMode(),
            $this->checkFrameworkHealth(),
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
    private function checkDemoModeEnabled(): array
    {
        if (! config('demo.enabled', false)) {
            return $this->result('demo_mode', 'fail', 'APP_DEMO_MODE=false — istanza non in modalità demo.');
        }

        return $this->result('demo_mode', 'ok', 'APP_DEMO_MODE attivo.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDemoEnvironment(): array
    {
        if (config('app.env') === 'production') {
            return $this->result('demo_env', 'fail', 'APP_ENV=production non ammesso su istanza demo — usare demo o staging.');
        }

        if (config('app.debug') === true) {
            return $this->result('demo_env', 'warn', 'APP_DEBUG=true (accettabile in demo/staging).');
        }

        return $this->result('demo_env', 'ok', 'Ambiente demo/staging ('.config('app.env').').');
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
    private function checkDemoForceSandbox(): array
    {
        if (! config('demo.rentri.force_sandbox_api', true)) {
            return $this->result('demo_sandbox', 'fail', 'RENTRI_DEMO_FORCE_SANDBOX=false — rischio chiamate api.rentri.gov.it.');
        }

        return $this->result('demo_sandbox', 'ok', 'Sandbox MASE forzata (demoapi.rentri.gov.it).');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkDemoSeed(bool $requireSeed): array
    {
        if ($this->demoSeed->isSeeded()) {
            return $this->result('demo_seed', 'ok', 'Fixture demo presenti (rentri:demo-seed).');
        }

        if ($requireSeed) {
            return $this->result('demo_seed', 'fail', 'Seed demo assente — eseguire php artisan rentri:demo-seed.');
        }

        return $this->result('demo_seed', 'warn', 'Seed demo assente — eseguire rentri:demo-seed dopo bootstrap.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkRentriStubMode(): array
    {
        if (config('demo.rentri.offline_no_http', false)) {
            return $this->result('rentri_stub', 'ok', 'Modalità offline (RENTRI_DEMO_NO_HTTP) — nessuna HTTP verso MASE.');
        }

        $settings = RentriSetting::instance();

        if (config('demo.rentri.live_sandbox', true)) {
            if (blank($settings->cert_path_encrypted)) {
                return $this->result('rentri_stub', 'warn', 'Palestra sandbox live — caricare certificato PKCS#12 DEMO in Impostazioni RENTRI.');
            }

            return $this->result('rentri_stub', 'ok', 'Integrazione live verso demoapi.rentri.gov.it (palestra operativa).');
        }

        if ($this->runtimeMode->isApiStub($settings)) {
            return $this->result('rentri_stub', 'ok', 'API RENTRI in stub — adatto a demo CI/staging.');
        }

        return $this->result('rentri_stub', 'warn', 'RENTRI_API_STUB disabilitato — richiede certificato sandbox MASE.');
    }

    /**
     * @return array{name: string, status: string, message: string}
     */
    private function checkFrameworkHealth(): array
    {
        try {
            $response = app()->handle(Request::create('/up', 'GET'));

            if ($response->getStatusCode() === 200) {
                return $this->result('framework_health', 'ok', 'Health check Laravel /up OK.');
            }

            return $this->result('framework_health', 'fail', 'Health /up ha risposto HTTP '.$response->getStatusCode().'.');
        } catch (\Throwable $e) {
            return $this->result('framework_health', 'fail', 'Health /up non raggiungibile: '.$e->getMessage());
        }
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
