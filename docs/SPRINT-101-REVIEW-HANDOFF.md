# Sprint 101 — Review handoff (agente Sprint 102)

**Destinatario:** agente Sprint 102 · **GPS provider contratto reale**.

**Riferimenti:** [SPRINT-101-AUDIT-NOTES.md](SPRINT-101-AUDIT-NOTES.md) · [CICLO-9-PIANO.md](CICLO-9-PIANO.md)

---

## Cosa è stato implementato (Sprint 101)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Endpoint config sandbox/prod | `MudTelematicoEndpoints.php` |
| 2 | Transmission wired to paths | `MudTelematicoTransmissionService.php` |
| 3 | UI submit URL + probe HEAD | `MudShow.php`, `show.blade.php` |
| 4 | Fixture contratto | `tests/fixtures/mud/mase-invio-submit.json` |
| 5 | Config env | `config/services.php`, `.env.example` |
| 6 | Test Sprint 101 | `tests/Feature/Sprint101/*` (7 test) |

---

## Istruzione ESATTA agente Sprint 102

**GPS provider — adapter contratto fornitore + geofencing alert:**

1. Audit `TrasportoGpsTrackingService` vs contratto API provider reale.
2. **`TrasportoGpsProviderAdapter`** — mapping campi response provider-specific.
3. Config env provider documentata; preflight UI.
4. Opzionale: geofencing alert stub → notifica hub.
5. Test Sprint 102 ≥6; regression 664+ verdi.
6. `docs/SPRINT-102-REVIEW-HANDOFF.md` + aggiornare `CICLO-9-PIANO.md`.
7. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 102

1. Poll GPS live con adapter provider configurabile.
2. UI TrasportoShow mostra stato provider + preflight.
3. Suite test verde.
