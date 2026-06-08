# Sprint 110 — Review handoff (ciclo 9 CHIUSO)

**Destinatario:** team ops / agente ciclo 10 · **RENTRI cert produzione + mobile prep**.

**Riferimenti:** [SPRINT-110-AUDIT-NOTES.md](SPRINT-110-AUDIT-NOTES.md) · [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)

---

## Cosa è stato implementato (Sprint 110)

| # | Deliverable | File |
|---|-------------|------|
| 1 | GO-LIVE produzione | `docs/GO-LIVE-PRODUZIONE.md` |
| 2 | Ciclo 9 CHIUSO | `CICLO-9-PIANO.md` + backlog §12 |
| 3 | Outline ciclo 10 | `docs/CICLO-10-PIANO-STUB.md` |
| 4 | README ciclo 9 | `README.md` |
| 5 | Test Sprint 110 | `tests/Feature/Sprint110/Cycle9ClosureGoLiveTest.php` |

---

## Esito ciclo 9

- **750 test** PHPUnit (4 skipped integration sandbox).
- Prep **completa in code** per MUD, GPS, Stripe, pen-test, WAF, RENTRI switch, Horizon/SMTP, HA, KPI v2.
- **Gap residui:** attivazione infra/contratti esterni — vedi [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md) §8.

---

## Istruzione ciclo 10 (Sprint 111+)

Vedi [CICLO-10-PIANO-STUB.md](CICLO-10-PIANO-STUB.md):

1. **Sprint 111** — RENTRI cert produzione validazione E2E ministeriale.
2. **Sprint 113** — Pen-test remediation findings vendor.
3. **Sprint 115** — Mobile app operatore prep (PWA/API).
4. Chiusura prevista sprint **120** — `GO-LIVE-CERT-PRODUZIONE.md`.

---

## Smoke post-chiusura

```bash
php -d memory_limit=512M vendor/bin/phpunit
php artisan test --filter=Sprint110
php artisan rentri:production-switch-check --dry-run
php artisan rentri:preflight --demo
```

**Admin UI:** `/admin/pen-test-prep` · `/admin/waf-status` · `/admin/ha-status` · `/segreteria` (KPI business v2).
