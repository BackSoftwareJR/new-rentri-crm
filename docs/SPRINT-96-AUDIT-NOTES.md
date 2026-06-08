# Sprint 96 — Audit notes: gateway pagamento e-commerce vs Stripe

**Data audit:** 4 giugno 2026  
**Scope:** checkout stub token vs Stripe Checkout Session live.

---

## 1. Gap pre-Sprint 96

**M-96-1:** `EcommerceCheckoutService` generava solo token monouso locale (`checkout_token` 32 char) — nessun wire verso gateway reale.

**M-96-2:** UI copy «stub» ovunque; nessun preflight chiavi Stripe; nessun webhook conferma pagamento.

---

## 2. Fix applicato

| Componente | Ruolo |
|------------|--------|
| `EcommercePaymentRuntimeModeService` | `ECOMMERCE_PAYMENT_STUB` → badge/copy stub vs live |
| `EcommercePaymentGatewayService` | Stub token / Stripe Checkout Session + webhook |
| `StripeCheckoutClient` | Wrapper `stripe/stripe-php` Session::create |
| `StripeEcommerceWebhookController` | `POST /webhooks/stripe/ecommerce` — firma se `STRIPE_WEBHOOK_SECRET` |
| UI ordine/carrello | Badge, preflight, link Stripe live |

---

## 3. Flussi

### Stub (default)
1. `avviaCheckout` → token 32 char
2. Operatore inserisce token → ordine `Confermato`

### Live Stripe
1. Preflight `STRIPE_KEY` + `STRIPE_WEBHOOK_SECRET`
2. `avviaCheckout` → Checkout Session URL
3. Webhook `checkout.session.completed` → ordine `Confermato`

---

## 4. Config

```env
ECOMMERCE_PAYMENT_STUB=false
STRIPE_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Webhook endpoint: `/webhooks/stripe/ecommerce`

---

## 5. Riferimenti

- [SPRINT-95-AUDIT-NOTES.md](SPRINT-95-AUDIT-NOTES.md) — pattern runtime stub/live
- [SPRINT-96-REVIEW-HANDOFF.md](SPRINT-96-REVIEW-HANDOFF.md)
