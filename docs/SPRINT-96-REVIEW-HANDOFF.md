# Sprint 96 — Review handoff (agente Sprint 97)

**Destinatario:** agente Sprint 97 · **2FA enforced admin/segreteria**.

**Riferimenti:** [SPRINT-96-AUDIT-NOTES.md](SPRINT-96-AUDIT-NOTES.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 96)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Runtime mode stub/live | `EcommercePaymentRuntimeModeService.php` |
| 2 | Gateway stub/Stripe | `EcommercePaymentGatewayService.php` |
| 3 | Stripe client wrapper | `StripeCheckoutClient.php` |
| 4 | Webhook conferma | `StripeEcommerceWebhookController.php` |
| 5 | Config + migration | `services.php`, migration stripe fields |
| 6 | UI badge + preflight | `EcommerceOrdineShow`, `EcommerceCarrello`, badge component |
| 7 | Test Sprint 96 | `tests/Feature/Sprint96/EcommerceStripeGatewayTest.php` (10 test) |

### Env chiave

- `ECOMMERCE_PAYMENT_STUB` (default `true`)
- `STRIPE_KEY`, `STRIPE_WEBHOOK_SECRET`

---

## Istruzione ESATTA agente Sprint 97

**2FA enforced admin/segreteria:**

1. Audit `TwoFactorSettings` / middleware login attuale.
2. Enforce 2FA obbligatorio per ruoli `admin` e `segreteria` (config + middleware).
3. UI onboarding 2FA + blocco accesso se non configurato.
4. Test Sprint 97 ≥6.
5. `docs/SPRINT-97-REVIEW-HANDOFF.md` + `CICLO-8-PIANO.md`
6. No commit/push salvo richiesta utente

---

## Output atteso agente Sprint 97

1. 2FA enforced documentato + regression verde (618+ test attesi).
2. Handoff Sprint 98 (tracking GPS trasporti).
