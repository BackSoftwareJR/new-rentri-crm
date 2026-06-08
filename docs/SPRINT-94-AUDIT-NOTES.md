# Sprint 94 — Audit notes: payload vidima vs OpenAPI MASE

**Data audit:** 4 giugno 2026  
**Scope:** body `RentriFirVidimaRequest` vs contratto vidimazione RENTRI v1.0.

---

## 1. Riferimenti

| Artefatto | Path |
|-----------|------|
| Request adapter | `app/Services/Rentri/Dto/RentriFirVidimaRequest.php` |
| Mapper trasmissione | `app/Services/Rentri/RentriFirVidimaTransmissionMapper.php` |
| Service vidima | `app/Services/Rentri/RentriFirService.php` |
| Fixture MASE | `tests/fixtures/rentri/mase/vidima-submit.json` |
| Audit ciclo 7 | `docs/CICLO-7-ENTERPRISE-AUDIT.md` — payload vidima «Parziale» |

---

## 2. Shape attesa MASE (POST vidimazione)

Endpoint live: `POST /vidimazione-formulari/v1.0/{codice_blocco}`

| Campo | Posizione | In body HTTP? |
|-------|-----------|---------------|
| `codice_blocco` | path param | ❌ (solo URL) |
| `num_iscr_sito` | body | ✅ obbligatorio |
| `progressivo` | body | ✅ opzionale |

---

## 3. Campi CRM (pre-fix audit)

`RentriFirService::vidima()` costruiva payload con metadati CRM:

| Campo | Scopo | Inviabile a MASE? |
|-------|-------|-------------------|
| `trasporto_id` | audit CRM / collegamento locale | ❌ |
| `codice_blocco` | duplicato path URL | ❌ |
| `num_iscr_sito` | duplicato costruttore | ✅ (deduplicato in body) |
| `progressivo` | richiesta vidima MASE | ✅ |

---

## 4. Mismatch documentato

**M-94-1:** `RentriFirVidimaRequest::body()` mergeava l'intero `$payload` inclusi `trasporto_id` e `codice_blocco` nel body HTTP.

**Impatto:** POST verso demoapi poteva contenere chiavi non previste dal contratto OpenAPI ministeriale.

**M-94-2:** Fixture Sprint 84 `vidima-submit.json` trattava `trasporto_id` come property MASE.

---

## 5. Fix applicato (Sprint 94)

1. **`RentriFirVidimaTransmissionMapper::forTransmission()`** — body con sole chiavi MASE (`num_iscr_sito`, `progressivo`).
2. **`RentriFirVidimaTransmissionMapper::crmAuditOnly()`** — estrae metadati CRM per audit locale.
3. **`RentriFirVidimaRequest::body()`** — usa mapper; `crmAuditPayload()` per audit.
4. **`RentriApiClient::submitFirVidima()`** — persiste `crm_audit` in `request_json` transazione (separato da `payload` ministeriale); in **stub** merge `crmAuditPayload()` nel contesto cache per sintesi esito (`codice_blocco` → `numero_fir`).
5. **Fixture** — `crm_excluded_keys`, `example_mase`, `example_crm_payload`, `path_params`.
6. **Test Sprint 94** — 7 test in `tests/Feature/Sprint94/*`.

---

## 6. Conformità post-fix

| Check | Esito |
|-------|-------|
| Body HTTP senza `trasporto_id` | ✅ |
| Body HTTP senza `codice_blocco` | ✅ |
| `crm_audit` in storico transazioni | ✅ |
| Contract fixture allineato | ✅ |
| Vidima service flow invariato lato CRM | ✅ |
| Stub vidima sintetizza `numero_fir` con `codice_blocco` CRM | ✅ |

**Residuo non bloccante:** validazione OpenAPI completa vs spec PDF ministeriale aggiornata — da confermare in sandbox con cert operatore.

---

## 7. Riferimenti incrociati

- [SPRINT-88-AUDIT-NOTES.md](SPRINT-88-AUDIT-NOTES.md) — pattern mapper COSE xFIR
- [SPRINT-94-REVIEW-HANDOFF.md](SPRINT-94-REVIEW-HANDOFF.md)
