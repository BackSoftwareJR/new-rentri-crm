<?php

namespace App\Domain\Trasporti;

use App\Domain\Notifications\NotificationService;
use App\Enums\NotificationEvent;
use App\Mail\TrasportoGpsGeofenceAlertMail;
use App\Models\Trasporto;

/**
 * Geofencing stub — alert se posizione oltre raggio da destinazione simulata.
 */
class TrasportoGpsGeofenceService
{
    public function isEnabled(): bool
    {
        return (bool) config('services.trasporto_gps.geofence_enabled', false);
    }

    public function radiusKm(): float
    {
        return max(0.1, (float) config('services.trasporto_gps.geofence_radius_km', 50));
    }

    /**
     * Destinazione stub deterministica per geofencing demo (no geocoding).
     *
     * @return array{latitude: float, longitude: float}
     */
    public function destinationStub(Trasporto $trasporto): array
    {
        $overrideLat = config('services.trasporto_gps.geofence_destination_lat');
        $overrideLng = config('services.trasporto_gps.geofence_destination_lng');

        if ($overrideLat !== null && $overrideLng !== null) {
            return [
                'latitude'  => (float) $overrideLat,
                'longitude' => (float) $overrideLng,
            ];
        }

        $seed = $trasporto->id;

        return [
            'latitude'  => round(45.46 + ($seed % 50) / 1000, 6),
            'longitude' => round(9.19 + ($seed % 40) / 1000, 6),
        ];
    }

    /**
     * @param  array{latitude: float, longitude: float}  $from
     * @param  array{latitude: float, longitude: float}  $to
     */
    public function distanceKm(array $from, array $to): float
    {
        $earthRadius = 6371.0;
        $latFrom = deg2rad($from['latitude']);
        $latTo = deg2rad($to['latitude']);
        $deltaLat = deg2rad($to['latitude'] - $from['latitude']);
        $deltaLng = deg2rad($to['longitude'] - $from['longitude']);

        $a = sin($deltaLat / 2) ** 2
            + cos($latFrom) * cos($latTo) * sin($deltaLng / 2) ** 2;
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return round($earthRadius * $c, 2);
    }

    /**
     * @param  array{latitude: float, longitude: float, recorded_at?: string, source?: string, speed_kmh?: float|null}  $position
     */
    public function checkAndNotify(Trasporto $trasporto, array $position): ?float
    {
        if (! $this->isEnabled()) {
            return null;
        }

        $destination = $this->destinationStub($trasporto);
        $distanceKm = $this->distanceKm(
            ['latitude' => $position['latitude'], 'longitude' => $position['longitude']],
            $destination,
        );

        if ($distanceKm <= $this->radiusKm()) {
            return $distanceKm;
        }

        app(NotificationService::class)->dispatch(
            NotificationEvent::TrasportoGpsGeofence,
            new TrasportoGpsGeofenceAlertMail($trasporto, $position, $destination, $distanceKm, $this->radiusKm()),
            context: [
                'trasporto_id' => $trasporto->id,
                'distance_km'  => $distanceKm,
                'radius_km'    => $this->radiusKm(),
            ],
        );

        return $distanceKm;
    }
}
