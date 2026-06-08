<?php

namespace App\Domain\Mud;

use Illuminate\Support\Facades\Http;

/**
 * Endpoint MUD telematico — gateway RENTRI-aligned (demoapi / api.rentri.gov.it).
 *
 * Il portale ufficiale presentazione manuale resta https://www.mudtelematico.it (SPID).
 * L'integrazione CRM usa il pattern async submit/poll/result analogo a registro RENTRI.
 *
 * @see docs/SPRINT-101-AUDIT-NOTES.md
 * @see App\Services\Rentri\RentriEndpoints
 */
class MudTelematicoEndpoints
{
    public const PORTAL_URL = 'https://www.mudtelematico.it';

    public const SUBMIT_PATH = '/mud/v1.0/dichiarazioni/trasmissione';

    public function environment(): string
    {
        $env = strtolower((string) config('services.mud_telematico.env', 'sandbox'));

        return in_array($env, ['production', 'prod'], true) ? 'production' : 'sandbox';
    }

    public function isProduction(): bool
    {
        return $this->environment() === 'production';
    }

    public function environmentLabel(): string
    {
        return $this->isProduction() ? 'produzione' : 'sandbox';
    }

    public function baseUrl(): string
    {
        $override = config('services.mud_telematico.base_url');

        if (is_string($override) && $override !== '') {
            return rtrim($override, '/');
        }

        $key = $this->isProduction() ? 'base_url_production' : 'base_url_sandbox';

        return rtrim((string) config("services.rentri.{$key}", ''), '/');
    }

    public function submitPath(): string
    {
        return (string) (config('services.mud_telematico.submit_path') ?: self::SUBMIT_PATH);
    }

    public function statusPath(string $transazioneId): string
    {
        $template = (string) (config('services.mud_telematico.status_path')
            ?: '/mud/v1.0/dichiarazioni/'.rawurlencode($transazioneId).'/status');

        return str_replace('{id}', rawurlencode($transazioneId), $template);
    }

    public function resultPath(string $transazioneId): string
    {
        $template = (string) (config('services.mud_telematico.result_path')
            ?: '/mud/v1.0/dichiarazioni/verifica/result');

        return str_replace('{id}', rawurlencode($transazioneId), $template);
    }

    /**
     * @return array<string, string>
     */
    public function resultQuery(string $transazioneId): array
    {
        return ['transazione_id' => $transazioneId];
    }

    public function submitUrl(): string
    {
        return $this->baseUrl().$this->submitPath();
    }

    public function statusUrl(string $transazioneId): string
    {
        return $this->baseUrl().$this->statusPath($transazioneId);
    }

    public function resultUrl(string $transazioneId): string
    {
        return $this->baseUrl().$this->resultPath($transazioneId);
    }

    /**
     * @return array{environment: string, base_url: string, submit_url: string, portal_url: string, submit_path: string}
     */
    public function summary(): array
    {
        return [
            'environment'  => $this->environment(),
            'base_url'     => $this->baseUrl(),
            'submit_url'   => $this->submitUrl(),
            'portal_url'   => self::PORTAL_URL,
            'submit_path'  => $this->submitPath(),
        ];
    }

    /**
     * Probe reachability via HEAD (fallback GET on 405).
     *
     * @return array{reachable: bool, status: ?int, method: string, error: ?string}
     */
    public function probeReachability(): array
    {
        $baseUrl = $this->baseUrl();

        if ($baseUrl === '') {
            return [
                'reachable' => false,
                'status'    => null,
                'method'    => 'HEAD',
                'error'     => 'Base URL non configurato.',
            ];
        }

        $timeout = (int) config('services.mud_telematico.timeout', 30);

        try {
            $response = Http::timeout(min($timeout, 10))
                ->withOptions(['allow_redirects' => false])
                ->send('HEAD', $baseUrl);

            $method = 'HEAD';
            $status = $response->status();

            if ($status === 405) {
                $response = Http::timeout(min($timeout, 10))
                    ->withOptions(['allow_redirects' => false])
                    ->get($baseUrl);
                $method = 'GET';
                $status = $response->status();
            }

            return [
                'reachable' => $status > 0 && $status < 500,
                'status'    => $status,
                'method'    => $method,
                'error'     => $status >= 500 ? 'HTTP '.$status : null,
            ];
        } catch (\Throwable $e) {
            return [
                'reachable' => false,
                'status'    => null,
                'method'    => 'HEAD',
                'error'     => $e->getMessage(),
            ];
        }
    }
}
