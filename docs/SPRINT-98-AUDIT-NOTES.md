# Sprint 98 — Audit notes: tracking GPS trasporti vs provider

**Data:** 4 giugno 2026  
**Scope:** stub OpenStreetMap search vs provider GPS HTTP live.

---

## Gap pre-Sprint 98 (M-98-1)

| Aspetto | Sprint 70 | Gap |
|---------|-----------|-----|
| Posizione veicolo | Nessuna — solo link OSM destinazione | ❌ |
| Provider API | Assente | ❌ |
| Persistenza | Nessuna | ❌ |
| UI refresh | Solo timeline stub | ❌ |

---

## Fix Sprint 98

| Componente | Ruolo |
|------------|--------|
| `TrasportoGpsRuntimeModeService` | `TRASPORTO_GPS_STUB` → badge stub/live |
| `TrasportoGpsTrackingService` | Poll stub / HTTP provider + persist JSON |
| Migration | `gps_last_position`, `gps_tracked_at` su `trasporti` |
| UI `TrasportoShow` | Badge, coordinate, mappa embed OSM, refresh |

---

## Contratto provider (placeholder)

`GET {TRASPORTO_GPS_PROVIDER_URL}/trasporti/{id}/position`

Header: `Authorization: Bearer {TRASPORTO_GPS_API_KEY}`

Response: `latitude`, `longitude`, `recorded_at`, `speed_kmh` (opzionale)

Fixture: `tests/fixtures/gps/position-response.json`

---

## Config

```env
TRASPORTO_GPS_STUB=false
TRASPORTO_GPS_PROVIDER_URL=https://gps-provider.example.com/api/v1
TRASPORTO_GPS_API_KEY=...
```

---

## Riferimenti

- [SPRINT-98-REVIEW-HANDOFF.md](SPRINT-98-REVIEW-HANDOFF.md)
- Sprint 70 baseline: `TrasportoTrackingPrepService`
