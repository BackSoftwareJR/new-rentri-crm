<?php

namespace App\Domain\Deploy;

use App\Domain\Rentri\RentriTransazioneService;
use App\Support\Demo\DemoContext;
use Illuminate\Http\Request;

class Cycle3MonitoringService
{
    public const HEALTH_ENDPOINT = '/up';

    public function __construct(
        private readonly RentriTransazioneService $rentriTransazioni,
    ) {}

    /**
     * Snapshot operativo per dashboard, alerting e `rentri:monitor`.
     *
     * @return array{
     *   framework_health: array{status: string, http_code: int|null, message: string},
     *   demo_mode: bool,
     *   app_env: string,
     *   rentri: array{totale: int, completate: int, errori: int, in_corso: int, dead_letter: int, retry_pianificati: int},
     *   alerts: list<array{level: string, code: string, message: string}>
     * }
     */
    public function snapshot(): array
    {
        $rentri = $this->rentriTransazioni->contatori();
        $frameworkHealth = $this->checkFrameworkHealth();

        return [
            'framework_health' => $frameworkHealth,
            'demo_mode'        => DemoContext::isActive(),
            'app_env'          => (string) config('app.env'),
            'rentri'           => $rentri,
            'alerts'           => $this->buildAlerts($rentri, $frameworkHealth),
        ];
    }

    /**
     * @return array{status: string, http_code: int|null, message: string}
     */
    public function checkFrameworkHealth(): array
    {
        try {
            $response = app()->handle(Request::create(self::HEALTH_ENDPOINT, 'GET'));
            $code = $response->getStatusCode();

            if ($code === 200) {
                return [
                    'status'    => 'ok',
                    'http_code' => $code,
                    'message'   => 'Health Laravel '.self::HEALTH_ENDPOINT.' OK.',
                ];
            }

            return [
                'status'    => 'fail',
                'http_code' => $code,
                'message'   => 'Health '.self::HEALTH_ENDPOINT.' ha risposto HTTP '.$code.'.',
            ];
        } catch (\Throwable $e) {
            return [
                'status'    => 'fail',
                'http_code' => null,
                'message'   => 'Health '.self::HEALTH_ENDPOINT.' non raggiungibile: '.$e->getMessage(),
            ];
        }
    }

    /**
     * @param  array{totale: int, completate: int, errori: int, in_corso: int, dead_letter: int, retry_pianificati: int}  $rentri
     * @param  array{status: string, http_code: int|null, message: string}  $frameworkHealth
     * @return list<array{level: string, code: string, message: string}>
     */
    private function buildAlerts(array $rentri, array $frameworkHealth): array
    {
        $alerts = [];

        if ($frameworkHealth['status'] !== 'ok') {
            $alerts[] = [
                'level'   => 'critical',
                'code'    => 'framework_health',
                'message' => $frameworkHealth['message'],
            ];
        }

        if ($rentri['dead_letter'] > 0) {
            $alerts[] = [
                'level'   => 'critical',
                'code'    => 'rentri_dead_letter',
                'message' => sprintf(
                    '%d transazione/i RENTRI in dead-letter — intervento manuale su /segreteria/rentri/transazioni.',
                    $rentri['dead_letter'],
                ),
            ];
        }

        if ($rentri['retry_pianificati'] > 0) {
            $alerts[] = [
                'level'   => 'warning',
                'code'    => 'rentri_retry_pending',
                'message' => sprintf(
                    '%d retry RENTRI pianificati — verificare queue Horizon.',
                    $rentri['retry_pianificati'],
                ),
            ];
        }

        if (DemoContext::isDeployDemo() && config('app.env') === 'production') {
            $alerts[] = [
                'level'   => 'critical',
                'code'    => 'demo_on_production_env',
                'message' => 'APP_DEMO_MODE=true con APP_ENV=production — configurazione deploy errata.',
            ];
        }

        if (! DemoContext::isActive() && config('app.env') === 'demo') {
            $alerts[] = [
                'level'   => 'warning',
                'code'    => 'prod_on_demo_env',
                'message' => 'APP_DEMO_MODE=false con APP_ENV=demo — verificare file env dell\'istanza.',
            ];
        }

        return $alerts;
    }
}
