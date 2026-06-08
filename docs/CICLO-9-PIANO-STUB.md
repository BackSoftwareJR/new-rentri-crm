# Ciclo 9 — Stub piano sprint 101–110

> **Superseded by [CICLO-9-PIANO.md](CICLO-9-PIANO.md)** — ciclo 9 **CHIUSO** · [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md).

**Stato:** ✅ CHIUSO · Prossimo: [CICLO-10-PIANO-STUB.md](CICLO-10-PIANO-STUB.md).

**Obiettivo suggerito:** chiudere gap residui post-ciclo 8 (contratti esterni, hardening infra, produzione multi-tenant) e consolidare go-live reale end-to-end.

**Baseline:** ciclo 8 chiuso — 657 test, 4 skipped · live prep completata per MUD, Stripe, GPS, SMTP, 2FA, RENTRI sandbox CI.

---

## Tabella sprint 101–110 (proposta)

| Sprint | Focus suggerito | Tipo | Priorità |
|--------|-----------------|------|----------|
| **101** | MUD telematico — endpoint MASE produzione + poll esito reale | Fix/Ops | P1 normativa |
| **102** | GPS provider — adapter contratto fornitore + geofencing alert | Fix | P2 ops |
| **103** | Stripe produzione — onboarding account, webhook prod, riconciliazione | Ops | P2 business |
| **104** | Pen-test OWASP esterno + remediation findings | Security | P1 |
| **105** | WAF deploy attivo + regole staging/prod | Infra | P1 |
| **106** | RENTRI produzione — switch controllato + runbook rollback | Ops | P0 |
| **107** | Horizon/queue scaling + notifiche SMTP volume | Infra | P2 |
| **108** | Multi-istanza demo/prod HA + backup/restore drill | Infra | P2 |
| **109** | Analytics KPI business (ordini, VFU, magazzino) dashboard v2 | Feature | P3 |
| **110** | Chiusura ciclo 9 GO-LIVE-PRODUZIONE | Docs | — |

---

## Gap ciclo 8 → ciclo 9

| Gap residuo | Sprint suggerito | Riferimento |
|-------------|------------------|-------------|
| Endpoint MUD MASE definitivo | 101 | GO-LIVE-OPERATIVO §8 |
| Contratto API GPS reale | 102 | GO-LIVE-OPERATIVO §8 |
| Stripe account produzione | 103 | GO-LIVE-OPERATIVO §8 |
| Pen-test third-party | 104 | GO-LIVE-360 §2 |
| WAF attivo | 105 | WAF-RULES-PREP.md |
| RENTRI prod switch | 106 | GO-LIVE-RENTRI.md |
| SMTP volume + queue | 107 | MONITORING-CICLO-3.md |
| HA / backup | 108 | DEPLOY-PRODUCTION.md |
| KPI business v2 | 109 | PERFORMANCE-MONITORING.md |

---

## Pattern consigliato

- Stesso schema ciclo 8: implement → audit notes → review handoff.
- Smoke chiusura ciclo: `tests/Feature/Sprint110/*` + doc `GO-LIVE-PRODUZIONE.md`.
- Nessun traffic MASE automatico su CI default (eredita Sprint 92).

---

## Riferimenti

- [CICLO-8-PIANO.md](CICLO-8-PIANO.md) (CHIUSO)
- [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md)
- [SPRINT-100-REVIEW-HANDOFF.md](SPRINT-100-REVIEW-HANDOFF.md)
