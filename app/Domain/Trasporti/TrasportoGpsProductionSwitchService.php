<?php

namespace App\Domain\Trasporti;

use App\Enums\TrasportoStato;
use App\Models\Trasporto;
use Illuminate\Support\Facades\Http;

/**
 * Checklist switch GPS stub → provider live produzione (Sprint 116).
 */
class TrasportoGpsProductionSwitchService
{
    public const RUNBOOK_DOC = 'docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md';

    public function __construct(
        private readonly TrasportoGpsRuntimeModeService $runtime,
        private readonly TrasportoGpsPreflightService $preflight,
        private readonly TrasportoGpsProviderAdapter $adapter,
    ) {}

    /**
     * Preset field map allineati a contratto provider (tests/fixtures/gps/position-response.json).
     *
     * @return array<string, array{label: string, field_map: array<string, string>}>
     */
    public function productionFieldMapPresets(): array
    {
        return [
            'flat_default' => [
                'label'     => 'Flat default (latitude, longitude)',
                'field_map' => [
                    'latitude'    => 'latitude',
                    'longitude'   => 'longitude',
                    'recorded_at' => 'recorded_at',
                    'speed_kmh'   => 'speed_kmh',
                ],
            ],
            'nested_fleet' => [
                'label'     => 'Nested fleet (location.lat/lng)',
                'field_map' => [
                    'latitude'    => 'location.lat',
                    'longitude'   => 'location.lng',
                    'recorded_at' => 'timestamp',
                    'speed_kmh'   => 'speed',
                ],
            ],
        ];
    }

    public function activeFieldMapPreset(): ?string
    {
        $current = $this->adapter->fieldMap();
        foreach ($this->productionFieldMapPresets() as $key => $preset) {
            if ($preset['field_map'] === array_intersect_key($current, $preset['field_map'])) {
                $matches = true;
                foreach ($preset['field_map'] as $field => $path) {
                    if (($current[$field] ?? '') !== $path) {
                        $matches = false;
                        break;
                    }
                }
                if ($matches) {
                    return $key;
                }
            }
        }

        return null;
    }

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}>
     */
    public function unifiedChecklist(): array
    {
        $providerUrl = rtrim((string) config('services.trasporto_gps.provider_url', ''), '/');
        $apiKey = (string) config('services.trasporto_gps.api_key', '');
        $map = $this->adapter->fieldMap();
        $preset = $this->activeFieldMapPreset();

        $items = [
            $this->item(
                'stub_off',
                'TRASPORTO_GPS_STUB=false',
                ! $this->runtime->isStub(),
                'Impostare TRASPORTO_GPS_STUB=false in .env produzione.',
                false,
                'env',
            ),
            $this->item(
                'provider_url',
                'Provider URL configurato (TRASPORTO_GPS_PROVIDER_URL)',
                $providerUrl !== '',
                'Base URL API provider GPS.',
                false,
                'env',
            ),
            $this->item(
                'provider_url_not_placeholder',
                'URL provider non placeholder (no example.com)',
                $providerUrl !== '' && ! str_contains($providerUrl, 'example.com'),
                'Sostituire gps-provider.example.com con endpoint contratto fornitore.',
                false,
                'env',
            ),
            $this->item(
                'api_key',
                'API key configurata (TRASPORTO_GPS_API_KEY)',
                $apiKey !== '',
                'Bearer token fornito dal vendor.',
                false,
                'env',
            ),
            $this->item(
                'field_map',
                'Field map lat/lng ('.$map['latitude'].', '.$map['longitude'].')',
                $map['latitude'] !== '' && $map['longitude'] !== '',
                'TRASPORTO_GPS_FIELD_LAT / TRASPORTO_GPS_FIELD_LNG — preset flat o nested.',
                false,
                'adapter',
            ),
            $this->item(
                'field_map_preset',
                'Field map allineato a preset produzione',
                $preset !== null,
                $preset !== null
                    ? 'Preset attivo: '.$preset
                    : 'Configurare preset flat_default o nested_fleet (vedi runbook).',
                true,
                'adapter',
            ),
            $this->item(
                'positions_path',
                'Path posizioni configurato',
                filled(config('services.trasporto_gps.positions_path')),
                'TRASPORTO_GPS_POSITIONS_PATH=/trasporti/{id}/position',
                false,
                'env',
            ),
        ];

        if (! $this->runtime->isStub()) {
            foreach ($this->preflight->checklist() as $preflightItem) {
                $items[] = $this->item(
                    'preflight_'.$preflightItem['key'],
                    $preflightItem['label'],
                    $preflightItem['ok'],
                    $preflightItem['hint'],
                    false,
                    'preflight',
                );
            }
        }

        $items[] = $this->item(
            'geofence_configured',
            'Geofencing alert configurato (opzionale)',
            ! (bool) config('services.trasporto_gps.geofence_enabled', false)
                || (filled(config('services.trasporto_gps.geofence_destination_lat'))
                    && filled(config('services.trasporto_gps.geofence_destination_lng'))),
            'TRASPORTO_GPS_GEOFENCE_* + destinazione lat/lng.',
            true,
            'geofence',
        );

        return $items;
    }

    public function canSwitchToLive(): bool
    {
        return collect($this->unifiedChecklist())
            ->reject(fn (array $item): bool => $item['optional'])
            ->every(fn (array $item): bool => $item['ok']);
    }

    public function isLiveActive(): bool
    {
        return ! $this->runtime->isStub() && $this->preflight->isReady();
    }

    /**
     * @return array{
     *     ready: bool,
     *     live_active: bool,
     *     mode: string,
     *     mode_label: string,
     *     ok: int,
     *     total: int,
     *     optional_pending: int,
     *     field_map_preset: ?string
     * }
     */
    public function summary(): array
    {
        $items = $this->unifiedChecklist();
        $required = collect($items)->reject(fn (array $i): bool => $i['optional']);

        return [
            'ready'              => $this->canSwitchToLive(),
            'live_active'        => $this->isLiveActive(),
            'mode'               => $this->runtime->modeKind(),
            'mode_label'         => $this->runtime->modeDisplayLabel(),
            'ok'                 => $required->where('ok', true)->count(),
            'total'              => $required->count(),
            'optional_pending'   => collect($items)->filter(fn (array $i): bool => $i['optional'])->reject(fn (array $i): bool => $i['ok'])->count(),
            'field_map_preset'   => $this->activeFieldMapPreset(),
        ];
    }

    /**
     * @return array{
     *     passed: bool,
     *     live_active: bool,
     *     checklist: list<array<string, mixed>>,
     *     probe: array<string, mixed>|null,
     *     summary: array<string, mixed>
     * }
     */
    public function dryRunReport(bool $withProbe = false): array
    {
        $probe = $withProbe ? $this->probeProvider() : null;
        $passed = $this->canSwitchToLive() && ($probe === null || ($probe['ok'] ?? false));

        if ($this->runtime->isStub()) {
            $passed = true;
        }

        return [
            'passed'      => $passed,
            'live_active' => $this->isLiveActive(),
            'checklist'   => $this->unifiedChecklist(),
            'probe'       => $probe,
            'summary'     => $this->summary(),
        ];
    }

    /**
     * @return array{ok: bool, message: string, http_status: ?int, sample: ?array<string, mixed>}
     */
    public function probeProvider(?int $transportId = null): array
    {
        if ($this->runtime->isStub()) {
            return [
                'ok'          => true,
                'message'     => 'Modalità stub — probe non necessario.',
                'http_status' => null,
                'sample'      => null,
            ];
        }

        if (! $this->preflight->isReady()) {
            $result = [
                'ok'          => false,
                'message'     => 'Preflight GPS non pronto — configurare URL e API key.',
                'http_status' => null,
                'sample'      => null,
            ];
            $this->logGpsProbe($result, $transportId);

            return $result;
        }

        $transportId ??= (int) config('services.trasporto_gps.probe_transport_id', 0);

        if ($transportId > 0) {
            $trasporto = Trasporto::query()->find($transportId);
            if ($trasporto !== null && $trasporto->stato === TrasportoStato::InTransito) {
                try {
                    $sample = app(TrasportoGpsTrackingService::class)->pollPosition($trasporto);

                    $result = [
                        'ok'          => true,
                        'message'     => 'Probe OK — trasporto #'.$transportId,
                        'http_status' => 200,
                        'sample'      => $sample,
                    ];
                    $this->logGpsProbe($result, $transportId);

                    return $result;
                } catch (TrasportoGpsTrackingException $e) {
                    $result = [
                        'ok'          => false,
                        'message'     => $e->getMessage(),
                        'http_status' => $e->getCode() > 0 ? $e->getCode() : null,
                        'sample'      => null,
                    ];
                    $this->logGpsProbe($result, $transportId);

                    return $result;
                }
            }
        }

        $result = $this->probeProviderHttp();
        $this->logGpsProbe($result, $transportId);

        return $result;
    }

    /**
     * @param  array<string, mixed>  $result
     */
    private function logGpsProbe(array $result, ?int $transportId): void
    {
        $logger = app(\App\Support\Logging\StructuredLogService::class);
        $context = [
            'entity_type' => $transportId ? 'trasporto' : null,
            'entity_id'   => $transportId ?: null,
            'outcome'     => ($result['ok'] ?? false) ? 'success' : 'failure',
            'context'     => [
                'http_status' => $result['http_status'] ?? null,
                'message'     => $result['message'] ?? null,
            ],
        ];

        if ($result['ok'] ?? false) {
            $logger->info('gps', 'provider_probe', 'Probe GPS provider OK', $context);

            return;
        }

        $logger->warning('gps', 'provider_probe', 'Probe GPS provider fallito', $context);
    }

    /**
     * @return list<array{step: int, action: string, detail: string}>
     */
    public function rollbackSteps(): array
    {
        return [
            [
                'step'   => 1,
                'action' => 'Impostare TRASPORTO_GPS_STUB=true',
                'detail' => 'Rollback immediato — posizioni simulate in CRM.',
            ],
            [
                'step'   => 2,
                'action' => 'Redeploy / cache config',
                'detail' => 'php artisan config:clear — verificare badge «GPS stub» su hub trasporti.',
            ],
            [
                'step'   => 3,
                'action' => 'Notifica fornitore',
                'detail' => 'Disattivare polling verso endpoint produzione se contratto sospeso.',
            ],
            [
                'step'   => 4,
                'action' => 'Monitoraggio trasporti in transito',
                'detail' => 'Ultima posizione gps_last_position resta in DB; refresh usa stub.',
            ],
        ];
    }

    public function runbookRelativePath(): string
    {
        return self::RUNBOOK_DOC;
    }

    /**
     * @return array{ok: bool, message: string, http_status: ?int, sample: ?array<string, mixed>}
     */
    private function probeProviderHttp(): array
    {
        $baseUrl = rtrim((string) config('services.trasporto_gps.provider_url', ''), '/');
        $probeId = (int) config('services.trasporto_gps.probe_transport_id', 1);
        $path = (string) config('services.trasporto_gps.positions_path', '/trasporti/{id}/position');
        $apiKey = (string) config('services.trasporto_gps.api_key', '');
        $url = $baseUrl.str_replace('{id}', (string) max(1, $probeId), $path);

        try {
            $response = Http::timeout((int) config('services.trasporto_gps.timeout', 15))
                ->withToken($apiKey)
                ->acceptJson()
                ->get($url);

            if (! $response->successful()) {
                return [
                    'ok'          => false,
                    'message'     => 'HTTP '.$response->status().': '.$response->body(),
                    'http_status' => $response->status(),
                    'sample'      => null,
                ];
            }

            /** @var array<string, mixed> $body */
            $body = $response->json() ?? [];
            $sample = $this->adapter->normalize($body);

            return [
                'ok'          => true,
                'message'     => 'Probe HTTP OK — field map valido.',
                'http_status' => $response->status(),
                'sample'      => $sample,
            ];
        } catch (TrasportoGpsTrackingException $e) {
            return [
                'ok'          => false,
                'message'     => $e->getMessage(),
                'http_status' => null,
                'sample'      => null,
            ];
        } catch (\Throwable $e) {
            return [
                'ok'          => false,
                'message'     => $e->getMessage(),
                'http_status' => null,
                'sample'      => null,
            ];
        }
    }

    /**
     * @return array{key: string, label: string, ok: bool, hint: ?string, optional: bool, group: string}
     */
    private function item(
        string $key,
        string $label,
        bool $ok,
        ?string $hint,
        bool $optional,
        string $group,
    ): array {
        return compact('key', 'label', 'ok', 'hint', 'optional', 'group');
    }
}
