# Sprint 32 — Vidima FIR reale end-to-end

**Data:** 4 giugno 2026  
**Ciclo:** Produzione RENTRI/FIR reale

---

## Obiettivo

Collegare `RentriFirService::vidima()` all'API ministeriale RENTRI v1.0 con flusso async (submit → poll status → result), sync blocchi FIR da API e feedback UI su protocollo/QR.

---

## Flusso vidimazione (live)

1. **POST** `/vidimazione-formulari/v1.0/{codice_blocco}` — body con payload trasporto; risposta `{ transazione_id }`
2. **GET** `/vidimazione-formulari/v1.0/{transazione_id}/status` — poll fino a `COMPLETATA` (config: `RENTRI_FIR_POLL_MAX_ATTEMPTS`, `RENTRI_FIR_POLL_INTERVAL_MS`)
3. **GET** `/vidimazione-formulari/v1.0/verifica/result?transazione_id=…` — `{ numero_fir, progressivo, protocollo, qr_code }`
4. Persistenza `Fir` con `qr_payload` JSON (protocollo, transazione_id, qr_code, api_mode)

Stub mode (`RENTRI_API_STUB=true`): stesso flusso logico in-memory via `Cache` (compatibile test).

---

## Sync blocchi FIR

- **GET** `/vidimazione-formulari/v1.0?identificativo={cf}&num_iscr_sito={sito}`
- `RentriFirBlocchiSync` → tabella `fir_blocchi`
- UI: pulsante «Sincronizza da RENTRI» in `/segreteria/fir/blocchi`

---

## Fuori scope (Sprint 34)

- Certificato firma xFIR COSE
- Validazione payload XSD xFIR

---

## Test

```bash
php artisan test --filter=Sprint32
php artisan test --filter=RentriFirServiceTest
php artisan test --filter=RentriApiClientTest
```

---

## Prossimo: Sprint 33

Trasmissione registro cronologico reale — vedi `docs/RENTRI_VERTICAL_BACKLOG.md` §6.
