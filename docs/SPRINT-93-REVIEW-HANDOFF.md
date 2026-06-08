# Sprint 93 — Review handoff (agente Sprint 94)

**Destinatario:** agente Sprint 94 · **Payload vidima OpenAPI alignment**.

**Contesto:** Ciclo 8 — SLA dashboard RENTRI completato.

**Riferimenti:** [CICLO-8-PIANO.md](CICLO-8-PIANO.md) · [SPRINT-88-AUDIT-NOTES.md](SPRINT-88-AUDIT-NOTES.md)

---

## Cosa è stato implementato (Sprint 93)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Aggregazione SLA 7/30 gg | `RentriSlaMetricsService.php` |
| 2 | UI hub RENTRI sezione SLA | `Rentri.php`, `rentri.blade.php` |
| 3 | Config soglie | `config/services.php`, `.env.example` |
| 4 | Test Sprint 93 | `tests/Feature/Sprint93/RentriSlaMetricsTest.php` (8 test) |

### Metriche

- **Latency** — avg, p50, p95 (created_at → completed_at) su transazioni completate
- **Retry** — media retry_count, conteggio con retry
- **Dead-letter** — count + rate % totale e per tipo `fir` / `xfir` / `registro`
- **Badge SLA** — ok / warn / fail vs `RENTRI_SLA_*`

---

## Checklist review Sprint 94

### Regression

```bash
php artisan test --filter=Sprint93
php artisan test --filter=Sprint45
php artisan test --filter=Sprint40
php artisan test
```

---

## Istruzione ESATTA agente Sprint 94

**Payload vidima OpenAPI alignment:**

1. Audit diff body `RentriFirVidimaRequest` vs fixture/OpenAPI MASE aggiornato.
2. Rimuovere o documentare campi CRM (`trasporto_id`) se non in spec — mapper dedicato se necessario.
3. Fixture + contract test estesi in `tests/fixtures/rentri/mase/`.
4. Test Sprint 94 ≥6.
5. `docs/SPRINT-94-AUDIT-NOTES.md` + `SPRINT-94-REVIEW-HANDOFF.md`
6. Aggiornare `CICLO-8-PIANO.md`
7. No commit/push salvo richiesta utente

**Riferimento audit:** `CICLO-7-ENTERPRISE-AUDIT.md` — payload vidima body «Parziale».

---

## Output atteso agente Sprint 94

1. Contract vidima allineato o gap documentato con fix mapper.
2. Regression suite verde (592+ test attesi).
3. Handoff Sprint 95 (MUD telematico live prep).
