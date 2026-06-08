<?php

namespace App\Domain\Trasporti;

/**
 * Normalizza risposte JSON provider GPS verso shape CRM canonica.
 *
 * @see docs/SPRINT-102-AUDIT-NOTES.md
 * @see tests/fixtures/gps/position-response.json
 */
class TrasportoGpsProviderAdapter
{
    /**
     * @return array{latitude: string, longitude: string, recorded_at: string, speed_kmh: string}
     */
    public function fieldMap(): array
    {
        /** @var array<string, string> $map */
        $map = config('services.trasporto_gps.field_map', []);

        return array_merge([
            'latitude'    => 'latitude',
            'longitude'   => 'longitude',
            'recorded_at' => 'recorded_at',
            'speed_kmh'   => 'speed_kmh',
        ], $map);
    }

    /**
     * @param  array<string, mixed>  $body
     * @return array{latitude: float, longitude: float, recorded_at: string, source: string, speed_kmh?: float|null}
     */
    public function normalize(array $body): array
    {
        $map = $this->fieldMap();

        $latitude = $this->readField($body, $map['latitude']);
        $longitude = $this->readField($body, $map['longitude']);

        if ($latitude === null || $longitude === null) {
            throw new TrasportoGpsTrackingException(
                'Risposta provider GPS senza coordinate valide (field map: '
                .$map['latitude'].', '.$map['longitude'].').',
            );
        }

        $lat = (float) $latitude;
        $lng = (float) $longitude;

        if ($lat === 0.0 && $lng === 0.0) {
            throw new TrasportoGpsTrackingException('Coordinate GPS nulle non valide.');
        }

        $recordedAt = $this->readField($body, $map['recorded_at']);
        $speed = $this->readField($body, $map['speed_kmh']);

        return [
            'latitude'    => $lat,
            'longitude'   => $lng,
            'recorded_at' => $recordedAt !== null ? (string) $recordedAt : now()->toIso8601String(),
            'source'      => 'live',
            'speed_kmh'   => $speed !== null ? (float) $speed : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $body
     */
    private function readField(array $body, string $path): mixed
    {
        if ($path === '') {
            return null;
        }

        if (! str_contains($path, '.')) {
            return $body[$path] ?? null;
        }

        $current = $body;
        foreach (explode('.', $path) as $segment) {
            if (! is_array($current) || ! array_key_exists($segment, $current)) {
                return null;
            }
            $current = $current[$segment];
        }

        return $current;
    }
}
