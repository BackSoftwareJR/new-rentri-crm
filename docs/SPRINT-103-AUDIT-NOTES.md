# Sprint 103 — Audit notes: Stripe produzione

**Data audit:** 4 giugno 2026 · **Ciclo 9**

---

## 1. Gap pre-Sprint 103

| Aspetto | Sprint 96 | Gap |
|---------|-----------|-----|
| Ambiente Stripe | Label generico «Stripe live» | ❌ no distinzione sandbox/produzione |
| Preflight | Secret + webhook only | ❌ no sk_live/sk_test, no EUR |
| Webhook idempotency | Nessuna | ❌ replay eventi duplicati |
| Riconciliazione | Solo activity log | ❌ no audit dedicato + notifica |

---

## 2. Implementazione

| Componente | Ruolo |
|------------|--------|
| `StripeProductionPreflightService` | sk_live/sk_test, STRIPE_LIVE_MODE, webhook, EUR |
| `EcommercePaymentRuntimeModeService` | Badge sandbox/produzione, dashboard URL |
| `StripeWebhookIdempotencyService` | Tabella `stripe_webhook_events` UNIQUE event id |
| `StripeReconciliationLogService` | Log canale + notifica hub |
| `EcommerceStripeReconciliationMail` | Email riconciliazione |

---

## 3. Config

```env
ECOMMERCE_PAYMENT_STUB=false
STRIPE_KEY=sk_test_...          # sandbox
STRIPE_WEBHOOK_SECRET=whsec_...
STRIPE_CURRENCY=eur
STRIPE_LIVE_MODE=false

# Produzione:
STRIPE_LIVE_MODE=true
STRIPE_KEY=sk_live_...
```

Dashboard: sandbox `https://dashboard.stripe.com/test/` · prod `https://dashboard.stripe.com/`

Webhook: `POST /webhooks/stripe/ecommerce`

---

## Riferimenti

- [SPRINT-103-REVIEW-HANDOFF.md](SPRINT-103-REVIEW-HANDOFF.md)
- [SPRINT-96-AUDIT-NOTES.md](SPRINT-96-AUDIT-NOTES.md)
