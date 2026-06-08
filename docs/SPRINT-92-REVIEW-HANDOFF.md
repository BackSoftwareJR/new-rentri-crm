# Sprint 92 — Review handoff (agente Sprint 93)

**Destinatario:** agente Sprint 93 · **SLA dashboard RENTRI** (latency, retry, dead-letter trends).

**Contesto:** Ciclo 8 — CI gated integration sandbox completato.

**Riferimenti:** [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md) §8 · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 92)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Workflow CI gated sandbox | `.github/workflows/rentri-sandbox-integration.yml` |
| 2 | Trigger `workflow_dispatch` + label `integration-sandbox` | workflow `on:` / job `if:` |
| 3 | Decode secret → temp p12 + cleanup | step decode + cleanup `always()` |
| 4 | Skip esplicito senza secrets | step gate + notice |
| 5 | Doc CI | `docs/VALIDAZIONE-SANDBOX-MASE.md` §8 |
| 6 | Test Sprint 92 | `tests/Feature/Sprint92/RentriSandboxIntegrationCiTest.php` (7 test) |

### Sicurezza CI

- Cert/password **mai** echo nei run step
- File temp in `RUNNER_TEMP`, chmod 600, rimosso post-run
- `production.yml` invariato — no traffic MASE su push default

---

## Checklist review Sprint 93

### Conformità Sprint 92

- [ ] Workflow esiste e non triggera su push main
- [ ] Skip senza secrets (exit 0)
- [ ] Con secrets → `RentriIntegrationTest` verso demoapi
- [ ] Doc §CI completa
- [ ] Production CI non include integration test

### Regression

```bash
php artisan test --filter=Sprint92
php artisan test --filter=Sprint91
php artisan test --filter=Sprint49
php artisan test
```

---

## Istruzione ESATTA agente Sprint 93

**SLA dashboard RENTRI:**

1. **`RentriSlaMetricsService`** — aggregazione da `rentri_transazioni`: latency (created→completed), retry_count trend, dead_letter rate per tipo (fir/xfir/registro).
2. **UI dashboard** — sezione su `/segreteria/rentri` o widget KPI: grafici/tabelle periodo 7/30 gg, badge SLA target (es. p95 latency, dead-letter %).
3. **Config** — soglie `RENTRI_SLA_*` opzionali in `config/services.php` + `.env.example`.
4. **Test Sprint 93** ≥6 (service aggregation, UI presence, empty state).
5. `docs/SPRINT-93-REVIEW-HANDOFF.md`
6. Aggiornare `CICLO-8-PIANO.md` sprint 93
7. No commit/push salvo richiesta utente

**Baseline dati:** `rentri_transazioni` (retry_count, dead_letter_at, created_at, completed_at, tipo).

---

## Output atteso agente Sprint 93

1. Dashboard SLA visibile in hub RENTRI.
2. Regression suite verde (584+ test attesi).
3. Handoff Sprint 94 (payload vidima OpenAPI).
