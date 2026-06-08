# Sprint 116 — Audit notes

**Focus:** GPS provider produzione — switch stub → live, field map preset, preflight.

---

## Deliverable verificati

| # | Item | Esito |
|---|------|-------|
| 1 | `TrasportoGpsProductionSwitchService` — checklist unificata, preset, probe, rollback | ✅ |
| 2 | `trasporto:gps-switch-check` — dry-run, `--probe`, `--json` | ✅ |
| 3 | UI `/segreteria/trasporti` — badge GPS, checklist switch, preset, rollback | ✅ |
| 4 | `docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md` | ✅ |
| 5 | Config `probe_transport_id` in `services.trasporto_gps` | ✅ |
| 6 | Test Sprint 116 ≥6 | ✅ |

---

## Field map produzione

Preset allineati a `tests/fixtures/gps/position-response.json`:

- **flat_default** — env default (`TRASPORTO_GPS_FIELD_*` top-level)
- **nested_fleet** — `location.lat/lng`, `timestamp`, `speed`

Rilevamento preset via `activeFieldMapPreset()` — voce opzionale in checklist.

---

## Gate switch live

Obbligatori (non opzionali):

- `TRASPORTO_GPS_STUB=false`
- URL provider non placeholder (`example.com` bloccato)
- API key
- Field map lat/lng
- Path posizioni
- Preflight items (URL, key, field map) in modalità live

Opzionali:

- Preset riconosciuto
- Geofence completo se abilitato

Stub mode: dry-run sempre `passed=true` (nessun probe richiesto).

---

## Probe

1. Stub → skip con messaggio
2. Live + preflight fail → errore configurazione
3. `TRASPORTO_GPS_PROBE_TRANSPORT_ID` + trasporto in transito → poll via `TrasportoGpsTrackingService`
4. Altrimenti → HTTP GET diretto con field map normalize

---

## Regressioni

Baseline Sprint 115: 796 test, 6 skipped. Sprint 116: **807 test**, 6 skipped, 11 nuovi in `Sprint116/`.
