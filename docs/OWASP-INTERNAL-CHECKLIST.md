# Checklist pen-test interno OWASP — CRM RENTRI autodemolitore

**Uso:** revisione periodica pre-go-live e dopo ogni sprint con impatto su auth, upload o API.  
**Scope:** applicazione Laravel (segreteria, operatore, admin, Livewire, RENTRI API stub/live, MUD, GPS, Stripe).

**Aggiornamento:** Sprint 104 · Ciclo 9 — post 2FA enforced, Stripe prod webhook, MUD/GPS endpoints.

---

## A01 — Broken Access Control

- [ ] Route segreteria protette da middleware `auth` + `role:segreteria|admin|editor`
- [ ] Route operatore isolate da segreteria (`role:operatore|admin|editor`)
- [ ] Livewire update route con middleware `auth` persistente
- [ ] Policy su modelli critici: VFU, FIR, trasporto, MUD, ordini e-commerce, bonifica
- [ ] Demo scope: cross-write `is_demo=false` negato in sessione palestra
- [ ] Gate `viewHorizon` limitato ad admin
- [ ] Re-check `authorize('view')` su azioni FIR (vidima, firma, trasmetti)
- [ ] Admin audit `/admin/audit` — solo ruolo `admin` (Sprint 104)
- [ ] Pen-test prep `/admin/pen-test-prep` — solo admin

## A02 — Cryptographic Failures

- [ ] Certificati PKCS#12 in storage privato, password cifrate (`Crypt`)
- [ ] `APP_KEY` configurata (`rentri:preflight`)
- [ ] HTTPS obbligatorio in produzione (infra)
- [ ] Nessun segreto in repository (`.env` escluso da git)
- [ ] Stripe webhook: verifica firma `Stripe-Signature` se `STRIPE_WEBHOOK_SECRET` configurato (Sprint 96/103)

## A03 — Injection

- [ ] Query Eloquent parametrizzate (no raw SQL utente)
- [ ] Validazione input Livewire su wizard VFU, impostazioni RENTRI, MUD
- [ ] Export JSON/PDF senza concatenazione HTML non escapata

## A04 — Insecure Design

- [ ] Rate limit login (`throttle:5,1`)
- [ ] Rate limit 2FA challenge (`two-factor.challenge_throttle`, default 5/min)
- [ ] Rate limit trasmissione registro RENTRI (3/min)
- [ ] Rate limit vidima/firma FIR (5/min per utente)
- [ ] Isolamento demo documentato e testato
- [ ] Webhook Stripe idempotency — replay `stripe_event_id` ignorato (Sprint 103)

## A05 — Security Misconfiguration

- [ ] `APP_DEBUG=false` in produzione (`rentri:preflight`)
- [ ] `RENTRI_API_STUB=false` in produzione (warn preflight)
- [ ] Permessi storage `bootstrap/cache`, `storage/` non world-writable
- [ ] Horizon non esposto pubblicamente senza gate admin
- [ ] `TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA=true` in produzione target (Sprint 97)
- [ ] Stripe: `sk_live_` solo con `STRIPE_LIVE_MODE` e webhook prod configurato (Sprint 103)

## A06 — Vulnerable Components

- [ ] `composer audit` senza vulnerabilità critical/high aperte
- [ ] `npm audit` su asset frontend (Vite)
- [ ] Dipendenze Laravel/Horizon aggiornate su branch release

## A07 — Identification & Authentication Failures

- [ ] Password hash bcrypt/argon (Laravel default)
- [ ] Logout invalida sessione
- [ ] CSRF token su form POST e Livewire
- [ ] Session fixation mitigata (regenerate on login — verificare)
- [ ] **2FA TOTP** — opt-in + enforced admin/segreteria (`EnsureTwoFactorEnabled`, Sprint 97)
- [ ] Grace period `TWO_FACTOR_ENFORCE_GRACE_UNTIL` comunicato prima blocco
- [ ] Challenge post-login `/login/two-factor-challenge` con throttle
- [ ] Operatore escluso da enforcement — verificare isolamento permessi

## A08 — Software & Data Integrity Failures

- [ ] Upload MIME whitelist (`UploadValidation`: PDF, PKCS#12)
- [ ] Mass assignment `$guarded` su Trasporto, Fir, VfuRegistration
- [ ] Fixture legacy read-only, import tracciato in audit log
- [ ] Stripe checkout metadata include `stripe_environment` (sandbox/prod audit)

## A09 — Security Logging & Monitoring Failures

- [ ] Activity log moduli: rentri, ecommerce, mud, legacy
- [ ] Indici query audit (`created_at`, `log_name+created_at`)
- [ ] Transazioni RENTRI con stato errore/dead-letter visibili in dashboard
- [ ] Riconciliazione Stripe webhook — log canale `notifications` + mail hub (Sprint 103)
- [ ] Tabella `stripe_webhook_events` per audit replay

## A10 — Server-Side Request Forgery (SSRF)

- [ ] Client RENTRI usa endpoint configurati (`demoapi` / `api.rentri.gov.it`)
- [ ] Nessun fetch URL arbitrario da input utente
- [ ] **MUD telematico** — outbound solo verso `MudTelematicoEndpoints` gateway (Sprint 101)
- [ ] **GPS provider** — URL da env `TRASPORTO_GPS_PROVIDER_URL`, adapter field map (Sprint 102)
- [ ] Demo: `api.rentri.gov.it` bloccato se `RENTRI_DEMO_FORCE_SANDBOX=true`

---

## Verifica rapida (comandi)

```bash
php artisan rentri:preflight
php artisan rentri:import-legacy --report
php artisan test --filter=UxSecurityQuickWinsTest
php artisan test --filter=FormSecurityRegistroTest
php artisan test --filter=DemoEcommerceMudIsolationTest
php artisan test --filter=TwoFactorEnforcementTest
php artisan test --filter=StripeProductionPreflightTest
php artisan test --filter=OwaspExternalPrepTest
composer audit
```

**Pen-test esterno:** vedi [PEN-TEST-EXTERNAL-SCOPE.md](PEN-TEST-EXTERNAL-SCOPE.md) · UI admin `/admin/pen-test-prep`.

---

## Esito revisione

| Data | Revisore | Esito | Note |
|------|----------|-------|------|
| 2026-06 | Sprint 104 | Prep esterno | Checklist aggiornata ciclo 9; vendor TBD |

**Prossimo passo ciclo 9:** RENTRI produzione switch (Sprint 106) · attivazione WAF edge = team infra.

---

## Riferimenti ciclo 9

| Feature | Sprint | Doc |
|---------|--------|-----|
| 2FA enforced | 97 | [2FA-PREP-RUNBOOK.md](2FA-PREP-RUNBOOK.md) |
| Stripe webhook idempotency | 103 | [SPRINT-103-AUDIT-NOTES.md](SPRINT-103-AUDIT-NOTES.md) |
| MUD endpoint MASE | 101 | [SPRINT-101-AUDIT-NOTES.md](SPRINT-101-AUDIT-NOTES.md) |
| GPS provider adapter | 102 | [SPRINT-102-AUDIT-NOTES.md](SPRINT-102-AUDIT-NOTES.md) |
| Pen-test prep | 104 | [PEN-TEST-EXTERNAL-SCOPE.md](PEN-TEST-EXTERNAL-SCOPE.md) |
