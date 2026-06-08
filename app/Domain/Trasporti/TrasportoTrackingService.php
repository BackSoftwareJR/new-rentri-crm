<?php

namespace App\Domain\Trasporti;

use App\Models\Trasporto;

class TrasportoTrackingService
{
    public function __construct(
        private TrasportoGpsTrackingService $gpsTracking,
    ) {}

    /**
     * URL mappa: posizione GPS se disponibile, altrimenti ricerca destinazione.
     */
    public function mapSearchUrl(Trasporto $trasporto): ?string
    {
        if (! $this->isTrackingAvailable($trasporto)) {
            return null;
        }

        $positionLink = $this->gpsTracking->openStreetMapLink(
            $this->gpsTracking->lastPosition($trasporto),
        );

        if ($positionLink !== null) {
            return $positionLink;
        }

        $query = trim((string) ($trasporto->destinatario?->ragione_sociale ?? ''));

        if ($query === '') {
            $query = 'Italia';
        }

        return 'https://www.openstreetmap.org/search?query='.urlencode($query);
    }

    public function isTrackingAvailable(Trasporto $trasporto): bool
    {
        return $this->gpsTracking->isTrackingAvailable($trasporto);
    }
}
