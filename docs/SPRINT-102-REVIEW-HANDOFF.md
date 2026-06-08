# Sprint 102 — Review handoff (agente Sprint 103)

**Destinatario:** agente Sprint 103 · **Stripe produzione onboarding**.

**Riferimenti:** [SPRINT-102-AUDIT-NOTES.md](SPRINT-102-AUDIT-NOTES.md) · [CICLO-9-PIANO.md](CICLO-9-PIANO.md)

---

## Cosa è stato implementato (Sprint 102)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Provider JSON adapter | `TrasportoGpsProviderAdapter.php` |
| 2 | Preflight checklist | `TrasportoGpsPreflightService.php` |
| 3 | Geofencing + notifica | `TrasportoGpsGeofenceService.php`, `TrasportoGpsGeofenceAlertMail.php` |
| 4 | UI preflight TrasportoShow | `show.blade.php` |
| 5 | Fixture contratto | `tests/fixtures/gps/position-response.json` |
| 6 | Test Sprint 102 | `tests/Feature/Sprint102/*` (8 test) |

---

## Istruzione ESATTA agente Sprint 103

**Stripe produzione — onboarding account, webhook prod, riconciliazione:**

1. Audit `EcommercePaymentGatewayService` vs Stripe live prod keys.
2. **`StripeProductionPreflightService`** — checklist sk_live, webhook prod, currency.
3. UI e-commerce — badge prod/sandbox, link dashboard Stripe.
4. Webhook prod idempotency + reconciliation stub report.
5. Test Sprint 103 ≥6; regression 672+ verdi.
6. `docs/SPRINT-103-REVIEW-HANDOFF.md` + aggiornare `CICLO-9-PIANO.md`.
7. No commit/push salvo richiesta utente.

---

## Output atteso agente Sprint 103

1. Checkout Stripe produzione documentato + preflight UI.
2. Webhook prod verificato in test con firma fake.
3. Suite test verde.
