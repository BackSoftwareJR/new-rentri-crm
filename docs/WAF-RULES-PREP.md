# Runbook prep WAF — CRM RENTRI autodemolitore

**Stato:** Sprint 105 · Ciclo 9 — prep applicativa completata; **attivazione regole = team infra**.

**Locale/dev:** WAF non attivo (`WAF_MODE=off`). **Staging/prod:** `monitor` → `block` ([WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md)).

**UI admin:** `/admin/waf-status` · Service: `WafDeploymentPreflightService`.

**Sprint 114:** cross-ref findings pen-test P0/P1 → path WAF; checklist `productionBlockChecklist()`; runbook tuning in UI.

---

## Obiettivo

Regole Web Application Firewall per staging/produzione, allineate OWASP Top 10 e superfici post-ciclo 9: login/2FA, Livewire, admin, Stripe webhook, upload cert RENTRI, Horizon.

---

## Modalità deploy (`WAF_MODE`)

| Valore | Comportamento | Uso |
|--------|---------------|-----|
| `off` | Nessuna regola edge | Locale, CI, dev |
| `monitor` | Count-only / log SIEM, zero block | Rollout fase 1 (48h+ prod) |
| `block` | Deny su match CRS / custom rules | Rollout fase 2 post-analisi FP |

Config: `config/waf.php` · env `WAF_MODE`, `WAF_PROVIDER`, `WAF_SIEM_LOG_GROUP`.

---

## Superfici da proteggere (post-ciclo 9)

| Path / pattern | Rischio | Regola WAF | Monitor | Block |
|----------------|---------|------------|---------|-------|
| `/login` | Brute force A07 | Rate limit IP 10/min | ✓ | ✓ |
| `/login/two-factor-challenge` | 2FA abuse A07 | Rate limit 5/min | ✓ | ✓ |
| `/livewire/update` | CSRF / injection A03 | Body cap 2 MB; CRS SQLi/XSS; 120/min auth | ✓ | ✓ |
| `/webhooks/stripe/ecommerce` | Replay / abuse A08 | Rate 60/min; **no block body** (firma HMAC) | ✓ | — |
| `/admin/audit`, `/admin/pen-test-prep`, `/admin/waf-status` | Privilege A01 | IP allowlist staging; admin only | ✓ | ✓ |
| `/segreteria/impostazioni/rentri` | Upload abuse A08 | MIME p12/pfx; max 512 KB | ✓ | ✓ |
| `/segreteria/*`, `/operatore/*` | IDOR / XSS | CRS paranoia 1 staging / 2 prod | ✓ | ✓ |
| `/horizon/*` | Queue exposure A05 | Solo admin + IP interni | ✓ | ✓ |
| `*.env`, `*.git` | Info disclosure A05 | Block always | — | ✓ |

---

## Stripe webhook — regole dedicate (Sprint 96/103)

1. **Non applicare** regole XSS al body JSON grezzo — Stripe invia payload firmato.
2. **Allowlist header** `Stripe-Signature` — non strip/modify.
3. **Rate limit** per IP Stripe (aggiornare range IP Stripe docs).
4. **Size cap** 256 KB sufficiente per `checkout.session.completed`.
5. **Idempotency** lato app (`stripe_webhook_events`) — WAF non sostituisce replay guard.

---

## Livewire — regole dedicate

1. **Escludere** header `X-Livewire` da bot detection.
2. **Cookie** `XSRF-TOKEN`, session — allowlist CSRF Laravel.
3. **Body JSON** Livewire — monitor SQLi 48h prima block; escludere snapshot base64 xFIR se match FP.
4. **Rate** 120 req/min per sessione autenticata su `/livewire/update`.

---

## Admin post-ciclo 9

| Path | Note WAF |
|------|----------|
| `/admin/audit` | Export CSV — no block su download autenticato admin |
| `/admin/pen-test-prep` | Solo admin; IP allowlist consigliato staging |
| `/admin/waf-status` | Read-only status; stesso profilo admin |

---

## Regole OWASP consigliate (AWS WAF / Cloudflare)

1. **Core rule set (CRS)** — paranoia level 1 staging / 2 prod.
2. **SQLi / XSS** — Block su query string; body Livewire in monitor prima.
3. **Size restrictions** — POST max 2 MB (upload cert path escluso).
4. **Bot control** — Challenge su `/login`; no Googlebot (app privata).
5. **Geo restriction** — IT + EU opzionale per `/segreteria/*`.
6. **Known bad inputs** — Block `../`, `<script`, `union select`.

---

## Esclusioni (false positive)

| Pattern | Motivo |
|---------|--------|
| Header `X-Livewire` | Framework SPA-like |
| Header `Stripe-Signature` | Webhook HMAC Stripe |
| Cookie `XSRF-TOKEN` | Laravel CSRF |
| Payload xFIR base64 | Firma digitale MASE |
| Campo `note` registro | Testo libero — monitor, regola scoped |

---

## Cross-ref findings pen-test → regole WAF (Sprint 114)

| Asset pen-test (`asset_key`) | Path WAF correlati | Note tuning |
|------------------------------|-------------------|-------------|
| `login` | `/login`, `/login/two-factor-challenge` | Rate limit; no block su 2FA legittimo |
| `segreteria` | `/livewire/update` | Esclusioni X-Livewire; monitor body 48h |
| `operatore` | `/livewire/update` | Stesso profilo Livewire |
| `admin_audit` | `/admin/audit`, `/admin/pen-test-prep`, `/admin/waf-status` | IP allowlist staging |
| `rentri_settings` | `/segreteria/impostazioni/rentri` | MIME p12/pfx; size cap |
| `stripe_webhook` | `/webhooks/stripe/ecommerce` | **Count-only body** — no block HMAC |

Findings P0/P1 aperti su asset mappati → **bloccare** passaggio `WAF_MODE=block` produzione fino a chiusura o regola aggiornata.

UI: `/admin/waf-status` tab cross-ref · Registro: `/admin/pen-test-prep`.

---

## Checklist pre-attivazione

- [ ] Pen-test interno OWASP aggiornato ciclo 9 (`docs/OWASP-INTERNAL-CHECKLIST.md`)
- [ ] Pen-test prep esterno documentato (`docs/PEN-TEST-EXTERNAL-SCOPE.md`)
- [ ] Login throttle Laravel (`throttle:5,1`) + 2FA challenge throttle
- [ ] Rate limit RENTRI trasmetti/FIR attivi
- [ ] `WAF_MODE=monitor` su staging; log → SIEM 90 gg
- [ ] Runbook 48h monitor → block (`docs/WAF-STAGING-ROLLOUT.md`)
- [ ] Findings P0/P1 su path WAF chiusi o regole aggiornate (Sprint 114)
- [ ] Rollback testato: switch `WAF_MODE=off` o count-only

---

## Rollout

Vedi [WAF-STAGING-ROLLOUT.md](WAF-STAGING-ROLLOUT.md):

1. **Staging monitor** — 1 settimana analisi false positive.
2. **Staging block** — SQLi/XSS + path sensibili.
3. **Produzione monitor** — 48h minimo (`WAF_MONITOR_HOURS_BEFORE_BLOCK`).
4. **Produzione block** — graduale per rule group.

---

## Fuori scope applicativo

- Provisioning infra AWS WAF / Cloudflare (DevOps).
- DDoS L3/L4 (CDN / Shield).
- Remediation findings pen-test vendor (`docs/REMEDIATION-FINDINGS-TEMPLATE.md`).

---

*Aggiornato Sprint 105 · Ciclo 9 · agg. Sprint 114 findings cross-ref.*
