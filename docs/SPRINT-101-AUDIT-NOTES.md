# Sprint 101 — Audit notes: MUD telematico endpoint MASE produzione

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Research ministeriale

| Fonte | Esito |
|-------|-------|
| [MASE MUD 2024/2025 — allegato presentazione telematica](https://www.mase.gov.it/portale/documents/d/guest/bando_mud_2025_allegato4_presentazione_telematica-pdf) | Invio **esclusivamente** via portale https://www.mudtelematico.it (SPID/CIE/CNS) |
| [mudtelematico.it](https://www.mudtelematico.it/) | Upload file tracciato record + firma digitale + pagamento diritti |
| API REST pubblica documentata | **Non disponibile** — nessun OpenAPI MUD analogo a RENTRI |

**Conclusione:** integrazione CRM non può replicare il flore web SPID del portale. Pattern adottato: **gateway RENTRI-aligned** (`demoapi.rentri.gov.it` / `api.rentri.gov.it`) con path async submit/poll/result coerente con `RentriEndpoints` (registro/vidima), in attesa di spec ministeriale ufficiale per API machine-to-machine.

---

## 2. Implementazione Sprint 101

| Componente | Ruolo |
|------------|-------|
| `MudTelematicoEndpoints` | sandbox/prod base URL, path v1.0, probe HEAD |
| `MudTelematicoTransmissionService` | wire HTTP via endpoints (non config path raw) |
| `MudShow` UI | submit URL completo, portale mudtelematico.it, reachability |
| Fixture | `tests/fixtures/mud/mase-invio-submit.json` aggiornato |

### Path RENTRI-aligned (default)

| Step | Path |
|------|------|
| Submit | `POST /mud/v1.0/dichiarazioni/trasmissione` |
| Status | `GET /mud/v1.0/dichiarazioni/{id}/status` |
| Result | `GET /mud/v1.0/dichiarazioni/verifica/result?transazione_id={id}` |

### Env

```env
MUD_TELEMATICO_STUB=false
MUD_TELEMATICO_ENV=sandbox   # o production
# Base auto: RENTRI_BASE_URL_SANDBOX / RENTRI_BASE_URL_PRODUCTION
# MUD_TELEMATICO_BASE_URL=   # override opzionale
```

---

## 3. Residuo post-Sprint 101

- Conferma path ufficiali quando MASE pubblicherà API MUD su gateway RENTRI (se diverso da `/mud/v1.0/*`).
- mTLS / auth: riuso cert RENTRI operatore (future sprint).
- Presentazione manuale resta su mudtelematico.it per soggetti senza integrazione CRM.

---

## Riferimenti

- [SPRINT-101-REVIEW-HANDOFF.md](SPRINT-101-REVIEW-HANDOFF.md)
- [CICLO-9-PIANO.md](CICLO-9-PIANO.md)
- [SPRINT-95-AUDIT-NOTES.md](SPRINT-95-AUDIT-NOTES.md)
