# Sprint 95 — Audit notes: invio telematico MUD vs ministeriale

**Data audit:** 4 giugno 2026  
**Scope:** `MudInvioTelematicoService` / protocollo `MUD-STUB-*` vs invio telematico ministeriale async.

---

## 1. Riferimenti

| Artefatto | Path |
|-----------|------|
| Invio orchestration | `app/Domain/Mud/MudInvioTelematicoService.php` |
| Transmission adapter | `app/Domain/Mud/MudTelematicoTransmissionService.php` |
| Runtime mode | `app/Domain/Mud/MudTelematicoRuntimeModeService.php` |
| Mapper body MASE | `app/Domain/Mud/MudTelematicoTransmissionMapper.php` |
| XML build/validate | `app/Domain/Mud/MudXmlValidationService.php` |
| Fixture contratto | `tests/fixtures/mud/mase-invio-submit.json` |
| Sprint 65 baseline | `tests/Feature/Sprint65/MudTelematicoTest.php` |

---

## 2. Stato pre-Sprint 95 (gap ciclo 6)

| Aspetto | Sprint 65 | Gap |
|---------|-----------|-----|
| Protocollo | `MUD-STUB-{anno}-{hash}` sincrono in-process | ❌ nessun wire HTTP |
| Async flow | Nessuno | ❌ no submit/poll/result |
| Config live | Assente | ❌ no `MUD_TELEMATICO_*` |
| UI | Solo copy «stub» | ❌ no badge stub/live |
| Body ministeriale | N/A (stub locale) | ❌ no separazione CRM |

**M-95-1:** Invio telematico limitato a stub protocollo locale senza adapter HTTP configurabile verso sandbox MASE.

**M-95-2:** Metadati CRM (`dichiarazione_id`, `totali`) non documentati come esclusi dal wire ministeriale.

---

## 3. Shape attesa invio telematico (placeholder sandbox)

Flusso async (pattern RENTRI vidima/registro):

| Step | Endpoint placeholder | Risposta attesa |
|------|---------------------|-----------------|
| Submit | `POST /mud-telematico/v1/invio` | `202` + `transazione_id` |
| Poll status | `GET /mud-telematico/v1/invio/{id}/status` | `stato: COMPLETATA` |
| Result | `GET /mud-telematico/v1/invio/{id}/result` | `protocollo`, `esito` |

### Body HTTP ministeriale (submit)

| Campo | In body HTTP? |
|-------|---------------|
| `anno_riferimento` | ✅ |
| `xml` (base64) | ✅ |
| `xml_encoding` | ✅ |
| `schema_version` | ✅ |
| `dichiarazione_id` | ❌ CRM audit |
| `totali` | ❌ CRM audit |

---

## 4. Fix applicato (Sprint 95)

1. **`MudTelematicoRuntimeModeService`** — `MUD_TELEMATICO_STUB` + demo offline → stub/live label UI.
2. **`MudTelematicoTransmissionService`** — submit async stub (cache) / live (HTTP configurable).
3. **`MudTelematicoTransmissionMapper`** — body MASE-only; `crm_audit` in `invio_risposta` live.
4. **`MudInvioTelematicoService::invia()`** — delega transmission; checklist endpoint live.
5. **UI `MudShow`** — badge stub/live, copy dinamica pulsante invio, URL canale live.
6. **Config** — `services.mud_telematico.*` + `.env.example`.
7. **Test Sprint 95** — 9 test in `tests/Feature/Sprint95/*`.

---

## 5. Conformità post-fix

| Check | Esito |
|-------|-------|
| Stub async (transazione_id + poll) | ✅ |
| Live HTTP submit/poll/result | ✅ (Http fake + placeholder URL) |
| Body senza `dichiarazione_id`/`totali` | ✅ |
| `crm_audit` in `invio_risposta` live | ✅ |
| UI badge + checklist endpoint live | ✅ |
| Retrocompat Sprint 65 `inviaStub()` | ✅ |

**Residuo non bloccante:** endpoint reale MASE MUD non ancora pubblicato/documentato — placeholder sandbox fino a spec ministeriale ufficiale. Schema XML resta `mud-stub-v1` (Sprint 65).

---

## 6. Config operatore

```env
MUD_TELEMATICO_STUB=false
MUD_TELEMATICO_BASE_URL=https://sandbox.mud-telematico.example.gov.it
```

---

## 7. Riferimenti incrociati

- [SPRINT-94-AUDIT-NOTES.md](SPRINT-94-AUDIT-NOTES.md) — pattern mapper async RENTRI
- [SPRINT-95-REVIEW-HANDOFF.md](SPRINT-95-REVIEW-HANDOFF.md)
- [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md) §6 gap MUD live
