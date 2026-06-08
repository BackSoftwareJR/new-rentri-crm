# Sprint 34 — Firma COSE xFIR

**Data:** 4 giugno 2026  
**Ciclo:** Produzione RENTRI/FIR reale

---

## Obiettivo

Certificato firma remota distinto da mTLS, firma COSE_Sign1 su payload xFIR post-vidima, validazione schema ministeriale e UI download payload firmato.

---

## Componenti

| Componente | Ruolo |
|------------|--------|
| `RentriFirmaCertificateService` | Storage PKCS#12 firma in `rentri/certificates/firma/` |
| `RentriXfirPayloadBuilder` | Costruzione payload xFIR v1.0 da FIR + trasporto |
| `RentriXfirValidator` | Validazione campi obbligatori schema MASE (subset) |
| `RentriXfirCoseSigner` | COSE_Sign1 stub (HMAC) o live (ES256 + PKCS#12) |
| `RentriFirSigningService` | Orchestrazione build → validate → sign → persist |

---

## Flusso firma

1. FIR in stato `vidimato` (post-vidima RENTRI)
2. Build payload xFIR (`versione`, `numero_fir`, `trasporto.codice_cer`, …)
3. Validazione campi obbligatori
4. Firma COSE_Sign1 → `xfir_signed_payload` JSON
5. Stato FIR → `firmato`, `firmato_at` valorizzato

Stub firma: `RENTRI_FIRMA_STUB=true` (default allineato a `RENTRI_API_STUB`).

---

## UI

- **Impostazioni RENTRI** — sezione «Certificato firma remota xFIR» (post-onboarding)
- **Dettaglio trasporto** — badge firma, pulsante «Firma xFIR», download JSON firmato

---

## Fuori scope (Sprint 35)

- Go-live produzione + runbook conformità
- Invio xFIR firmato a endpoint ministeriale di trasmissione

---

## Test

```bash
php artisan test --filter=Sprint34
php artisan test --filter=RentriFirServiceTest
```

---

## Prossimo: Sprint 35

Go-live RENTRI prod + runbook conformità — vedi `docs/RENTRI_VERTICAL_BACKLOG.md` §6.
