<?php

namespace App\Domain\Trasporti;

use App\Enums\TrasportoStato;
use App\Models\Trasporto;
use Illuminate\Support\Facades\Http;

/**
 * Adapter tracking GPS trasporti — stub vs provider HTTP live.
 *
 * @see docs/SPRINT-98-AUDIT-NOTES.md
 * @see docs/SPRINT-102-AUDIT-NOTES.md
 */
class TrasportoGpsTrackingService
{
    public function __construct(
        private TrasportoGpsRuntimeModeService $runtime,
        private TrasportoGpsProviderAdapter $providerAdapter,
        private TrasportoGpsGeofenceService $geofence,
    ) {}

    public function isTrackingAvailable(Trasporto $trasporto): bool
    {
        return $trasporto->stato === TrasportoStato::InTransito;
    }

    /**
     * @return array{latitude: float, longitude: float, recorded_at: string, source: string, speed_kmh?: float|null}
     */
    public function pollPosition(Trasporto $trasporto): array
    {
        if (! $this->isTrackingAvailable($trasporto)) {
            throw new TrasportoGpsTrackingException('Tracking GPS disponibile solo per trasporti in transito.');
        }

        if ($this->runtime->isStub()) {
            return $this->pollStubPosition($trasporto);
        }

        return $this->pollLivePosition($trasporto);
    }

    public function refreshPosition(Trasporto $trasporto): Trasporto
    {
        $position = $this->pollPosition($trasporto);

        $trasporto->update([
            'gps_last_position' => $position,
            'gps_tracked_at'    => now(),
        ]);

        $fresh = $trasporto->fresh();
        $this->geofence->checkAndNotify($fresh, $position);

        return $fresh;
    }

    /**
     * @return array{latitude: float, longitude: float, recorded_at: string, source: string, speed_kmh?: float|null}|null
     */
    public function lastPosition(Trasporto $trasporto): ?array
    {
        /** @var array<string, mixed>|null $position */
        $position = $trasporto->gps_last_position;

        if ($position === null || ! isset($position['latitude'], $position['longitude'])) {
            return null;
        }

        return [
            'latitude'    => (float) $position['latitude'],
            'longitude'   => (float) $position['longitude'],
            'recorded_at' => (string) ($position['recorded_at'] ?? ''),
            'source'      => (string) ($position['source'] ?? 'unknown'),
            'speed_kmh'   => isset($position['speed_kmh']) ? (float) $position['speed_kmh'] : null,
        ];
    }

    public function openStreetMapLink(?array $position): ?string
    {
        if ($position === null) {
            return null;
        }

        $lat = $position['latitude'];
        $lon = $position['longitude'];

        return sprintf(
            'https://www.openstreetmap.org/?mlat=%F&mlon=%F#map=14/%F/%F',
            $lat,
            $lon,
            $lat,
            $lon,
        );
    }

    public function openStreetMapEmbedUrl(?array $position): ?string
    {
        if ($position === null) {
            return null;
        }

        $lat = $position['latitude'];
        $lon = $position['longitude'];
        $delta = 0.05;

        return sprintf(
            'https://www.openstreetmap.org/export/embed.html?bbox=%F,%F,%F,%F&layer=mapnik&marker=%F,%F',
            $lon - $delta,
            $lat - $delta,
            $lon + $delta,
            $lat + $delta,
            $lat,
            $lon,
        );
    }

    /**
     * @return array{latitude: float, longitude: float, recorded_at: string, source: string, speed_kmh: float}
     */
    private function pollStubPosition(Trasporto $trasporto): array
    {
        $seed = $trasporto->id;
        $latitude = 45.0 + ($seed % 100) / 1000;
        $longitude = 9.0 + ($seed % 80) / 1000;

        return [
            'latitude'    => round($latitude, 6),
            'longitude'   => round($longitude, 6),
            'recorded_at' => now()->toIso8601String(),
            'source'      => 'stub',
            'speed_kmh'   => 65.0 + ($seed % 20),
        ];
    }

    /**
     * @return array{latitude: float, longitude: float, recorded_at: string, source: string, speed_kmh?: float|null}
     */
    private function pollLivePosition(Trasporto $trasporto): array
    {
        $baseUrl = rtrim((string) config('services.trasporto_gps.provider_url', ''), '/');
        $path = (string) config('services.trasporto_gps.positions_path', '/trasporti/{id}/position');
        $apiKey = (string) config('services.trasporto_gps.api_key', '');

        if ($baseUrl === '') {
            throw new TrasportoGpsTrackingException('TRASPORTO_GPS_PROVIDER_URL non configurato.');
        }

        if ($apiKey === '') {
            throw new TrasportoGpsTrackingException('TRASPORTO_GPS_API_KEY non configurato.');
        }

        $url = $baseUrl.str_replace('{id}', (string) $trasporto->id, $path);

        $response = Http::timeout((int) config('services.trasporto_gps.timeout', 15))
            ->withToken($apiKey)
            ->acceptJson()
            ->get($url);

        if (! $response->successful()) {
            throw new TrasportoGpsTrackingException(
                'Polling GPS fallito: '.$response->body(),
                $response->status(),
            );
        }

        /** @var array<string, mixed> $body */
        $body = $response->json() ?? [];

        return $this->providerAdapter->normalize($body);
    }
}
