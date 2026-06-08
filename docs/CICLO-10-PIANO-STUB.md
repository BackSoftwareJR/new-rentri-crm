# Ciclo 10 — Stub piano sprint 111–120

> **Superseded by [CICLO-10-PIANO.md](CICLO-10-PIANO.md)** — ciclo 10 **CHIUSO** · sign-off [GO-LIVE-CERT-PRODUZIONE.md](GO-LIVE-CERT-PRODUZIONE.md).

**Stato:** Ciclo 10 ✅ CHIUSO (sprint 111–120 completati).

**Obiettivo suggerito:** validazione certificato RENTRI in produzione end-to-end, hardening post go-live, preparazione mobile app operatore, osservabilità avanzata e automazione ops.

**Baseline:** ciclo 9 chiuso — 750 test, 4 skipped · prep infra completata (WAF, HA, Horizon, pen-test, Stripe, RENTRI switch).

---

## Tabella sprint 111–120 (proposta)

| Sprint | Focus suggerito | Tipo | Priorità |
|--------|-----------------|------|----------|
| **111** | RENTRI cert produzione — validazione E2E ministeriale | Ops | P0 |
| **112** | Post go-live monitoring — alert SLA + dead-letter automation | Ops | P1 |
| **113** | Pen-test remediation — chiusura findings vendor | Security | P1 |
| **114** | WAF produzione block mode — tuning regole post-deploy | Infra | P1 |
| **115** | Mobile app operatore — API prep + PWA shell | Feature | P2 |
| **116** | GPS provider produzione — contratto fornitore live | Fix/Ops | P2 |
| **117** | Stripe reconciliation prod — reporting + dispute stub | Ops | P2 |
| **118** | HA failover drill — esercitazione multi-istanza | Infra | P2 |
| **119** | Analytics KPI v3 — export CSV business + alert email | Feature | P3 |
| **120** | Chiusura ciclo 10 GO-LIVE-CERT-PRODUZIONE | Docs | — |

---

## Gap ciclo 9 → ciclo 10

| Gap residuo | Sprint suggerito | Riferimento |
|-------------|------------------|-------------|
| RENTRI cert prod E2E | 111 | GO-LIVE-PRODUZIONE §8 |
| Pen-test vendor execution | 113 | PEN-TEST-EXTERNAL-SCOPE.md |
| WAF block tuning prod | 114 | WAF-STAGING-ROLLOUT.md |
| Mobile app nativa/PWA | 115 | operatore `/operatore/*` |
| GPS contratto live | 116 | GO-LIVE-PRODUZIONE §8 |
| HA drill eseguito | 118 | HA-BACKUP-DRILL-RUNBOOK.md |
| KPI alert automatici | 119 | KPI-BUSINESS-DASHBOARD-V2.md |

---

## Pattern consigliato

- Stesso schema ciclo 9: implement → audit notes → review handoff.
- Smoke chiusura ciclo: `tests/Feature/Sprint120/*` + doc aggiornamento GO-LIVE.
- Nessun traffic MASE automatico su CI default (eredita Sprint 92).
- Sprint 111 richiede finestra manutenzione e cert operatore produzione.

---

## Riferimenti

- [CICLO-9-PIANO.md](CICLO-9-PIANO.md) (CHIUSO)
- [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md)
- [SPRINT-110-REVIEW-HANDOFF.md](SPRINT-110-REVIEW-HANDOFF.md)
