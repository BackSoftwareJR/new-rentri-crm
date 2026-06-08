# Pen-test OWASP esterno — Scope engagement

**Versione:** Sprint 104 · **Ciclo 9** · **Data:** giugno 2026

**Destinatario:** vendor security assessment (black-box / grey-box web application)

**Applicazione:** CRM RENTRI autodemolitore — Laravel 11, Livewire, Spatie roles, integrazioni MASE/Stripe/GPS.

---

## 1. Obiettivo

Valutare la postura di sicurezza dell'applicazione web **prima del go-live produzione**, con focus su OWASP Top 10 (2021), access control, autenticazione 2FA, webhook payment e isolamento demo/prod.

**Deliverable atteso:** report findings con severità (Critical/High/Medium/Low/Info) + proof-of-concept; remediation tracciata in [REMEDIATION-FINDINGS-TEMPLATE.md](REMEDIATION-FINDINGS-TEMPLATE.md).

---

## 2. Ambiente target

| Parametro | Valore consigliato |
|-----------|-------------------|
| **URL** | Staging dedicato (es. `https://staging.crm.example.it`) — **non produzione** |
| **APP_ENV** | `staging` |
| **APP_DEBUG** | `false` |
| **APP_DEMO_MODE** | `false` (o istanza demo separata per test isolamento) |
| **RENTRI_API_STUB** | `true` o sandbox `demoapi.rentri.gov.it` — **no API MASE produzione** |
| **ECOMMERCE_PAYMENT_STUB** | `false` con `STRIPE_KEY=sk_test_...` |
| **NOTIFICATIONS_LIVE** | `false` (log channel) |
| **TRASPORTO_GPS_STUB** | `true` |

**Finestra test:** da concordare (tipico 5–10 giorni lavorativi).

**Orari:** CET business hours; notificare team prima di scan aggressivi.

---

## 3. Asset in scope

| # | Superficie | Path / pattern | Auth | Note |
|---|------------|----------------|------|------|
| 1 | Login | `/login` | No | Rate limit 5/min |
| 2 | 2FA challenge | `/login/two-factor-challenge` | Parziale | TOTP post-password |
| 3 | Segreteria | `/segreteria/*` | Sì (segreteria/admin/editor) | Livewire SPA-like |
| 4 | Operatore | `/operatore/*` | Sì (operatore/admin/editor) | 2FA non enforced |
| 5 | Admin audit | `/admin/audit`, `/admin/pen-test-prep` | Sì (admin) | Export CSV |
| 6 | Impostazioni RENTRI | `/segreteria/impostazioni/rentri` | Sì | Upload PKCS#12 |
| 7 | Impostazioni sicurezza | `/segreteria/impostazioni/sicurezza` | Sì | Setup 2FA |
| 8 | E-commerce | `/segreteria/ecommerce/*` | Sì | Checkout Stripe test |
| 9 | Webhook Stripe | `POST /webhooks/stripe/ecommerce` | Firma HMAC | Idempotency event id |
| 10 | MUD | `/segreteria/mud/*` | Sì | Submit async (outbound MASE) |
| 11 | Trasporti GPS | `/segreteria/trasporti/*` | Sì | Posizione provider stub |
| 12 | Health | `/up` | No | Laravel health |

**Outbound (server-side, grey-box):** chiamate verso gateway RENTRI sandbox, Stripe test API, GPS mock — documentare ma non attaccare infrastruttura ministeriale.

---

## 4. Fuori scope

| Area | Motivo |
|------|--------|
| API RENTRI/MASE **produzione** (`api.rentri.gov.it`) | Traffico normativo reale |
| Addebiti Stripe **live** (`sk_live_`) | Solo test mode |
| Horizon `/horizon` esposto pubblicamente | Gate admin + IP interni |
| Regole WAF edge (AWS/Cloudflare) | Sprint 105 infra — config read-only on request |
| SMTP relay produzione | Stub/log in staging |
| DoS/DDoS volumetrico | Responsabilità CDN/infra |
| Social engineering / phishing | Fuori scope technical |
| Codice sorgente terze parti (vendor PHP/npm) | Solo `composer audit` review |

---

## 5. Account test (fornire al vendor)

Creare su staging **prima** dell'engagement (template in `OwaspExternalPrepService::testAccountsTemplate()`):

| Ruolo | Scopo test |
|-------|------------|
| **admin** | Privilege escalation, audit export, admin paths |
| **segreteria** | 2FA enforced, wizard VFU/FIR/MUD, upload cert |
| **operatore** | Isolamento da segreteria, bonifica |
| **demo** (opz.) | Isolamento `is_demo` su istanza demo |

**2FA:** fornire seed TOTP dedicati (non produzione) per admin/segreteria.

**Password:** generare con password manager; rotazione post-test.

---

## 6. Metodologia suggerita

1. **Recon** — mappatura path, tecnologie (Laravel, Livewire, session cookie).
2. **AuthN/AuthZ** — bypass ruoli, IDOR su VFU/FIR/ordini/MUD, 2FA bypass.
3. **Injection** — SQLi, XSS stored/reflected su note registro, Livewire payloads.
4. **File upload** — certificati non-P12, path traversal.
5. **Business logic** — cross-demo write, doppio webhook Stripe, replay eventi.
6. **Crypto/config** — cookie flags, HTTPS, secret leakage.
7. **API outbound** — SSRF via configurazione provider (GPS URL).

**Tooling accettato:** Burp Suite Pro, OWASP ZAP, nuclei (rate-limited), manual testing.

---

## 7. Vincoli operativi

- **No destructive testing** — no DROP TABLE, no cancellazione massiva dati.
- **No load test** oltre 50 req/s sostenuti senza accordo.
- **Segnalare P0 immediatamente** — contatto security referente entro 4h.
- **Dati:** solo fixture/sintetici su staging; no PII clienti reali.

---

## 8. Riferimenti interni

| Documento | Contenuto |
|-----------|-----------|
| [OWASP-INTERNAL-CHECKLIST.md](OWASP-INTERNAL-CHECKLIST.md) | Baseline A01–A10 post-ciclo 9 |
| [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md) | Isolamento demo/prod |
| [REMEDIATION-FINDINGS-TEMPLATE.md](REMEDIATION-FINDINGS-TEMPLATE.md) | Tracking findings |
| [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) | Env checklist ciclo 8–9 |
| [WAF-RULES-PREP.md](WAF-RULES-PREP.md) | Regole WAF (post pen-test) |
| [2FA-PREP-RUNBOOK.md](2FA-PREP-RUNBOOK.md) | Enforcement TOTP |
| [SPRINT-103-AUDIT-NOTES.md](SPRINT-103-AUDIT-NOTES.md) | Stripe webhook idempotency |

**UI admin prep:** `/admin/pen-test-prep` (ruolo admin).

---

## 9. Contatti

| Ruolo | Nome | Email |
|-------|------|-------|
| Security referente | | |
| Tech lead | | |
| Product owner | | |

---

*Engagement brief Sprint 104 — aggiornare URL e contatti prima dell'invio al vendor.*
