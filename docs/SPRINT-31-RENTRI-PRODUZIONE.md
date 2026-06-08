# Sprint 31 — RENTRI produzione: ricerca e implementazione

**Data:** 4 giugno 2026  
**Ciclo:** Produzione RENTRI/FIR reale (post-MVP sprint 1–30)

---

## 1. Fonti ministeriali e ufficiali

| Fonte | URL | Contenuto rilevante |
|-------|-----|---------------------|
| Portale RENTRI | https://www.rentri.gov.it/ | Iscrizione operatori, FIR digitale obbligo 13/02/2026, servizi interoperabilità |
| API Docs DEMO | https://demoapi.rentri.gov.it/docs | Documentazione Interoperabilità RENTRI v1.0, flussi FIR, registro |
| API Docs PROD | https://api.rentri.gov.it/ | Stesso schema endpoint in produzione |
| D.D. 143/2023 MASE | https://www.mase.gov.it/portale/documents/d/guest/decreto_direttoriale_06112023_143_tracciabilita_rifiuti_allegato-pdf | Modalità operative, accesso API con certificato eIDAS o CA dominio RENTRI |
| Assolombarda / Ecocerved | PDF FIR digitale 2025 | mTLS/interoperabilità, distinzione certificato interoperabilità vs certificato firma remota (sigillo xFIR) |

### Server API (da documentazione ufficiale)

| Ambiente | Base URL |
|----------|----------|
| Demo / Sandbox | `https://demoapi.rentri.gov.it` |
| Produzione | `https://api.rentri.gov.it` |

---

## 2. Autenticazione e sicurezza

### MVP (sprint 1–30)

- `RENTRI_API_STUB=true` — nessuna HTTP reale
- Header custom `X-RENTRI-Signature` con HMAC stub
- Certificato: solo nome file cifrato in DB, nessun storage reale

### Produzione (Sprint 31+)

Per **Decreto 143/2023, Modalità operativa 18** e documentazione demoapi:

1. **Certificato interoperabilità** — PKCS#12 (.p12/.pfx) rilasciato da CA dominio RENTRI (MO 16) o eIDAS qualificato (e-seal o rappresentante incaricato)
2. **Trasporto** — **mTLS** (mutual TLS): il client presenta il certificato nella handshake HTTPS
3. **Certificato firma xFIR** — **distinto** dal certificato interoperabilità; usato per firma COSE_Sign1 su FIR digitale (Sprint 32+)

Implementazione Sprint 31:

- Upload PKCS#12 → `storage/app/private/rentri/certificates/` (disk `local`)
- Password keystore → colonna `cert_password_encrypted` (Laravel `Crypt`)
- Live mode: `Http::withOptions(['cert' => [path, password]])`
- Stub mode: mantiene HMAC legacy per dev senza certificati reali

---

## 3. Gap MVP → produzione

| Area | MVP | Sprint 31 | Rimanente (32–35) |
|------|-----|-----------|-------------------|
| Base URL API | `sandbox.api.rentri.gov.it` (fittizio) | `demoapi.rentri.gov.it` / `api.rentri.gov.it` | — |
| Auth HTTP | HMAC stub | mTLS PKCS#12 | Firma COSE xFIR (certificato firma) |
| Health check | `GET /health` stub | `GET /vidimazione-formulari/v1.0` (blocchi FIR) | — |
| Codifiche CER | fixture JSON | `GET /codifiche/v1.0/cer` live | Conferma path da portal se diverso |
| Vidima FIR | `POST /fir/vidima` stub | DTO + live path + async poll | ✅ Sprint 32 |
| Registro trasmissione | stub | path mappato `/registro/v1.0/trasmissione` | ✅ Sprint 33 payload MASE + async |
| UI impostazioni | wizard base | stato connessione, stub/live badge, test connessione | CA dominio download assistito |
| Errori API | generici | messaggi IT 401/403/422/5xx + correlation id | Retry/backoff MASE |

---

## 4. Endpoint reali mappati (v1.0)

| Uso CRM | Endpoint logico interno | Path HTTP live (RENTRI v1.0) |
|---------|----------------------|------------------------------|
| Health / test connessione | — | `GET /vidimazione-formulari/v1.0?identificativo={cf}&num_iscr_sito={sito}` |
| Sync codifiche CER | `/codifiche/cer` | `GET /codifiche/v1.0/cer` |
| Vidima FIR | `/fir/vidima` | `POST /vidimazione-formulari/v1.0/{codice_blocco}` |
| Trasmissione registro | `/registro/trasmetti` | `POST /registro/v1.0/trasmissione` (da confermare su portal) |
| Stato async vidima | — | `GET /vidimazione-formulari/v1.0/{transazione_id}/status` |

Classe: `App\Services\Rentri\RentriEndpoints`

---

## 5. Flussi FIR / registro (conformità)

### FIR digitale (autodemolitore)

1. Iscrizione operatore su rentri.gov.it (SPID/CNS/CIE)
2. Attivazione interoperabilità + rilascio certificato CA dominio
3. Recupero blocchi FIR vidimati (`GET vidimazione-formulari/v1.0`) — **Sprint 32 ✅**
4. Vidimazione nuovo FIR (`POST /{codice_blocco}` → poll status → result) — **Sprint 32 ✅**
5. Compilazione / firma xFIR (COSE) — **Sprint 34 ✅**
6. Trasmissione dati rifiuti pericolosi — **Sprint 33**

### Registro cronologico

- Trasmissione periodica movimenti — **Sprint 33 ✅** (`POST` + poll status + protocollo)
- Allineamento con movimenti già in CRM (`RegistroMovimento`)

---

## 6. Checklist conformità operativa

- [x] Certificato PKCS#12 in storage sicuro, password cifrata
- [x] Flag `RENTRI_API_STUB` visibile in UI
- [x] Test connessione con endpoint ministeriale reale (demo)
- [x] Logging transazioni + correlation id
- [x] Messaggi errore utente in italiano
- [x] Certificato firma remota RENTRI (distinto) per xFIR
- [x] Validazione payload xFIR (subset schema v1.0)
- [x] Vidima FIR end-to-end in sandbox con credenziali operatore (client + test Http::fake)
- [x] Trasmissione registro accettata da API prod/demo (client + test Http::fake)
- [x] Procedure indisponibilità servizi (runbook GO-LIVE-RENTRI §4)

---

## 7. Variabili ambiente

```env
RENTRI_BASE_URL_SANDBOX=https://demoapi.rentri.gov.it
RENTRI_BASE_URL_PRODUCTION=https://api.rentri.gov.it
RENTRI_API_STUB=false          # go-live API reale
RENTRI_AUTH_MODE=mtls
RENTRI_VERIFY_SSL=true
RENTRI_INTEGRATION_TEST=false  # true solo per test manuali con cert reale
RENTRI_FIR_POLL_MAX_ATTEMPTS=15
RENTRI_FIR_POLL_INTERVAL_MS=200
RENTRI_REGISTRO_POLL_MAX_ATTEMPTS=15
RENTRI_REGISTRO_POLL_INTERVAL_MS=200
RENTRI_FIRMA_STUB=true           # firma COSE stub (default = RENTRI_API_STUB)
```

---

## 8. Test

```bash
php artisan test --filter=Sprint31
php artisan test --filter=RentriApiClientTest
RENTRI_API_STUB=false RENTRI_INTEGRATION_TEST=true php artisan test --filter=RentriIntegrationTest
```

---

## 9. Pattern UX (CRM autodemolitori)

Nessun CRM open-source italiano con integrazione RENTRI completa trovato in ricerca pubblica. Pattern adottati:

- Wizard impostazioni centralizzato (certificato + test connessione)
- Badge stato connessione (stub / sandbox / prod / scaduto)
- Separazione certificato **interoperabilità** vs **firma documento** (documentato in UI)

---

*Sprint 31 — implementazione account + client API reale + test connessione.*
