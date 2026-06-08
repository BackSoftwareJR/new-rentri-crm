# Sprint 115 — Review handoff (agente Sprint 116)

**Destinatario:** agente Sprint 116 · **GPS provider produzione — contratto fornitore live**.

**Riferimenti:** [SPRINT-115-AUDIT-NOTES.md](SPRINT-115-AUDIT-NOTES.md) · [CICLO-10-PIANO.md](CICLO-10-PIANO.md)

---

## Cosa è stato implementato (Sprint 115)

| # | Deliverable | File |
|---|-------------|------|
| 1 | API JSON read-only | `OperatoreMobileApiService.php`, `OperatoreApiController.php` |
| 2 | PWA manifest + SW | `OperatorePwaManifestController.php`, `operatore-sw.js` |
| 3 | Layout operatore PWA | `layouts/operatore.blade.php` (manifest + SW inline) |
| 4 | Doc cache/offline | `OPERATORE-PWA.md` |
| 5 | Config | `config/operatore.php` |
| 6 | Test Sprint 115 | `tests/Feature/Sprint115/OperatorePwaTest.php` |

---

## Istruzione ESATTA agente Sprint 116

**GPS provider produzione — contratto fornitore live:**

1. Integrazione provider GPS reale (env `TRASPORTO_GPS_*`) con adapter field map produzione.
2. Preflight/switch check produzione vs stub — UI o comando artisan.
3. Runbook contratto fornitore + fallback stub documentato.
4. Test Sprint 116 ≥6; regression 796+ verdi.
5. `docs/SPRINT-116-REVIEW-HANDOFF.md` + aggiornare `CICLO-10-PIANO.md`.
6. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 116

1. GPS live configurabile con gate go-live.
2. Stub/staging fallback documentato.
3. Suite test verde.
