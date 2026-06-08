# Sprint 35 — Go-live RENTRI produzione

**Data:** 4 giugno 2026  
**Ciclo:** Produzione RENTRI/FIR reale — **chiusura ciclo 2**

---

## Obiettivo

Checklist go-live, preflight esteso, runbook operativo, documentazione deploy e test integrazione opzionali.

---

## Deliverable

1. **`PreflightService`** — check certificato firma xFIR, dati operatore, stub firma in production
2. **`docs/GO-LIVE-RENTRI.md`** — variabili prod, smoke E2E, runbook (MASE, rotazione cert, rollback stub)
3. **`RentriIntegrationTest`** — health + blocchi + codifiche (dietro `RENTRI_INTEGRATION_TEST`)
4. **README + backlog** — tabella ciclo 2, ciclo marcato CHIUSO

---

## Test

```bash
php artisan test --filter=Sprint35
php artisan test --filter=PreflightCommandTest
php artisan rentri:preflight
```

---

## Gap residui post-ciclo (manutenzione)

- Validazione XSD xFIR completa ministeriale
- Invio payload xFIR firmato a endpoint dedicato (se distinto da vidima)
- Pen test / audit sicurezza produzione
- Automazione retry/backoff MASE in job queue
