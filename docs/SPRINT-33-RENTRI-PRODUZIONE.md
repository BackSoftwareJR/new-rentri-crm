# Sprint 33 — Trasmissione registro cronologico reale

**Data:** 4 giugno 2026  
**Ciclo:** Produzione RENTRI/FIR reale

---

## Obiettivo

Collegare `RentriRegistryService::transmit()` all'API ministeriale RENTRI v1.0 con payload MASE conforme, flusso async (submit → poll status → result) e feedback UI su protocollo accettazione.

---

## Flusso trasmissione (live)

1. **POST** `/registro/v1.0/trasmissione` — body MASE (`identificativo`, `num_iscr_sito`, `periodo_dal/al`, `movimenti[]`)
2. **GET** `/registro/v1.0/{transazione_id}/status` — poll fino a `COMPLETATA` (config: `RENTRI_REGISTRO_POLL_MAX_ATTEMPTS`, `RENTRI_REGISTRO_POLL_INTERVAL_MS`)
3. **GET** `/registro/v1.0/verifica/result?transazione_id=…` — `{ esito, protocollo }`
4. Persistenza `RentriTransmissione.response_json` con protocollo, transazione_id, api_mode

Stub mode: stesso flusso logico in-memory via `Cache`.

---

## Mapping movimenti CRM → MASE

| CRM (`RegistroMovimento`) | MASE |
|---------------------------|------|
| `codice_cer` | `codice_cer` |
| `tipo` (carico/scarico) | `tipo_movimento` (CARICO/SCARICO) |
| `peso_kg` | `quantita_kg` |
| `data_movimento` | `data_movimento` (YYYY-MM-DD) |
| `id` | `riferimento_interno` |

Classe adapter: `RentriRegistroTrasmissioneRequest`

---

## Fuori scope (Sprint 34)

- Certificato firma xFIR COSE
- Validazione payload XSD xFIR

---

## Test

```bash
php artisan test --filter=Sprint33
php artisan test --filter=RentriRegistryServiceTest
php artisan test --filter=RentriHttpTest
```

---

## Prossimo: Sprint 34

Firma COSE certificato dominio + validazione xFIR — vedi `docs/RENTRI_VERTICAL_BACKLOG.md` §6.
