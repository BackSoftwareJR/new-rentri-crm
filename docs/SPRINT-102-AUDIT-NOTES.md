# Sprint 102 — Audit notes: GPS provider contratto reale

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Gap pre-Sprint 102

| Aspetto | Sprint 98 | Gap |
|---------|-----------|-----|
| Field mapping | Hardcoded `lat`/`lng` fallback | ❌ no config provider-specific |
| Preflight UI | Solo copy provider URL | ❌ no checklist URL + API key |
| Geofencing | Assente | ❌ no alert distanza destinazione |
| Contratto fixture | Flat keys only | ❌ no varianti nested |

---

## 2. Implementazione

| Componente | Ruolo |
|------------|--------|
| `TrasportoGpsProviderAdapter` | Normalizza JSON via `field_map` (dot notation) |
| `TrasportoGpsPreflightService` | Checklist URL, API key, field map |
| `TrasportoGpsGeofenceService` | Haversine vs destinazione stub + notifica |
| `TrasportoGpsGeofenceAlertMail` | Email hub `trasporto.gps_geofence` |
| UI `TrasportoShow` | Preflight live + disable refresh se KO |

---

## 3. Config field map

```env
TRASPORTO_GPS_FIELD_LAT=latitude
TRASPORTO_GPS_FIELD_LNG=longitude
# Nested provider:
TRASPORTO_GPS_FIELD_LAT=location.lat
TRASPORTO_GPS_FIELD_LNG=location.lng
TRASPORTO_GPS_FIELD_RECORDED_AT=timestamp
TRASPORTO_GPS_FIELD_SPEED=speed
```

### Geofencing (stub)

```env
TRASPORTO_GPS_GEOFENCE_ENABLED=true
TRASPORTO_GPS_GEOFENCE_RADIUS_KM=50
# TRASPORTO_GPS_GEOFENCE_DEST_LAT=45.46
# TRASPORTO_GPS_GEOFENCE_DEST_LNG=9.19
```

Destinazione default: coordinate stub deterministiche da `trasporto.id` (no geocoding indirizzo).

---

## 4. Fixture

`tests/fixtures/gps/position-response.json` — varianti `flat_default` e `nested_fleet`.

---

## Riferimenti

- [SPRINT-102-REVIEW-HANDOFF.md](SPRINT-102-REVIEW-HANDOFF.md)
- [SPRINT-98-AUDIT-NOTES.md](SPRINT-98-AUDIT-NOTES.md)
