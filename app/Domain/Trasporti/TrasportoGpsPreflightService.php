<?php

namespace App\Domain\Trasporti;

/**
 * Checklist preflight provider GPS (live mode).
 */
class TrasportoGpsPreflightService
{
    public function __construct(
        private TrasportoGpsRuntimeModeService $runtime,
        private TrasportoGpsProviderAdapter $adapter,
    ) {}

    /**
     * @return list<array{key: string, label: string, ok: bool, hint: ?string}>
     */
    public function checklist(): array
    {
        if ($this->runtime->isStub()) {
            return [];
        }

        $providerUrl = rtrim((string) config('services.trasporto_gps.provider_url', ''), '/');
        $apiKey = (string) config('services.trasporto_gps.api_key', '');
        $map = $this->adapter->fieldMap();

        return [
            [
                'key'   => 'provider_url',
                'label' => 'Provider URL configurato (TRASPORTO_GPS_PROVIDER_URL)',
                'ok'    => $providerUrl !== '',
                'hint'  => 'Impostare URL base API provider GPS.',
            ],
            [
                'key'   => 'api_key',
                'label' => 'API key configurata (TRASPORTO_GPS_API_KEY)',
                'ok'    => $apiKey !== '',
                'hint'  => 'Bearer token per autenticazione provider.',
            ],
            [
                'key'   => 'field_map',
                'label' => 'Field map lat/lng ('.$map['latitude'].', '.$map['longitude'].')',
                'ok'    => $map['latitude'] !== '' && $map['longitude'] !== '',
                'hint'  => 'Configurare TRASPORTO_GPS_FIELD_LAT / TRASPORTO_GPS_FIELD_LNG se necessario.',
            ],
        ];
    }

    public function isReady(): bool
    {
        $items = $this->checklist();

        if ($items === []) {
            return true;
        }

        return collect($items)->every(fn (array $item) => $item['ok']);
    }
}
