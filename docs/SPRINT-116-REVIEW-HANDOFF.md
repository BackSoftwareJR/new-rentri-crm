# Sprint 116 — Review handoff (agente Sprint 117)

**Destinatario:** agente Sprint 117 · **Stripe reconciliation prod — reporting + dispute stub**.

**Riferimenti:** [SPRINT-116-AUDIT-NOTES.md](SPRINT-116-AUDIT-NOTES.md) · [CICLO-10-PIANO.md](CICLO-10-PIANO.md) · [GPS-PROVIDER-PRODUZIONE-RUNBOOK.md](GPS-PROVIDER-PRODUZIONE-RUNBOOK.md)

---

## Cosa è stato implementato (Sprint 116)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Switch service GPS live | `TrasportoGpsProductionSwitchService.php` |
| 2 | Comando preflight/switch | `TrasportoGpsSwitchCheckCommand.php` |
| 3 | UI hub trasporti | `TrasportiIndex.php`, `trasporti/index.blade.php` |
| 4 | Runbook contratto + fallback stub | `docs/GPS-PROVIDER-PRODUZIONE-RUNBOOK.md` |
| 5 | Config probe trasporto | `config/services.php` → `probe_transport_id` |
| 6 | Test Sprint 116 | `tests/Feature/Sprint116/TrasportoGpsProductionSwitchTest.php` |

---

## Istruzione ESATTA agente Sprint 117

**Stripe reconciliation prod — reporting + dispute stub:**

1. `StripeProductionPreflightService` — checklist switch test/live (chiavi, webhook, dispute endpoint).
2. Reporting reconciliation pagamenti prod vs CRM (export o hub segreteria/admin).
3. Dispute stub / workflow prep documentato.
4. Comando artisan e/o UI preflight analoga a pattern Sprint 106/116.
5. Test Sprint 117 ≥6; regression suite Sprint 116+ verdi.
6. `docs/SPRINT-117-REVIEW-HANDOFF.md` + aggiornare `CICLO-10-PIANO.md`.
7. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 117

1. Stripe prod configurabile con gate go-live e reconciliation visibile.
2. Dispute handling stub documentato per ciclo 10.
3. Suite test verde.

---

## Note per Sprint 117

- Pattern di riferimento: `RentriProductionSwitchService` (106), `TrasportoGpsProductionSwitchService` (116).
- Backlog: [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §13 sprint 117.
- Baseline test post-116: vedi output PHPUnit in audit notes.
