# Ciclo 8 — Validazione operativa reale RENTRI + continuità automatica ✅ CHIUSO

**Sprint 91–100** · Partenza: ciclo 7 chiuso (569 test, GO-LIVE-ENTERPRISE) · **Chiusura:** 649 test, [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md)

**Obiettivo:** passare da remediation enterprise (P0–P2) a **validazione operativa reale** con sandbox MASE, CI gated, SLA, gap infra cicli 5–6, chiusura documentale.

**Pattern:** implement → review handoff (Sprint 92, 94, … REVIEW ONLY dove indicato).

**Baseline:** [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md) · [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md)

---

## Tabella sprint 91–100

| Sprint | Focus | Tipo | Stato |
|--------|-------|------|-------|
| **91** | Validazione live cert sandbox + `RentriIntegrationTest` hardened + UI wizard «Test reale MASE» | Fix | ✅ |
| **92** | CI gated integration test sandbox | Test/CI | ✅ |
| **93** | SLA dashboard RENTRI (latency, retry, dead-letter trends) | Ops | ✅ |
| **94** | Payload vidima OpenAPI alignment | Fix | ✅ |
| **95** | MUD invio telematico live prep (non stub protocol) | Fix | ✅ |
| **96** | Gateway pagamento e-commerce (Stripe stub configurabile) | Fix | ✅ |
| **97** | 2FA enforced admin/segreteria | Security | ✅ |
| **98** | Tracking GPS trasporti provider prep | Fix | ✅ |
| **99** | SMTP notifiche live + template | Ops | ✅ |
| **100** | Chiusura ciclo 8 GO-LIVE-OPERATIVO | Docs | ✅ |

---

## Sprint 91 — ✅ completato

1. **`RentriSandboxValidationService`** — orchestrazione test reale: prerequisiti, health, codifiche CER, vidima dry-run documentato.
2. **UI RentriSettings** — sezione «Validazione reale sandbox MASE» step-by-step + link demoapi.
3. **`RentriIntegrationTest`** — skip se mancano `RENTRI_INTEGRATION_TEST` + `RENTRI_SANDBOX_CERT_PATH`.
4. **`docs/VALIDAZIONE-SANDBOX-MASE.md`** — guida operatore cert + env.
5. **Preflight** — check opzionale `RENTRI_SANDBOX_CERT_PATH` se configurato.
6. **Test Sprint 91** — 8 test in `tests/Feature/Sprint91/*`.

### File principali

- `app/Domain/Rentri/RentriSandboxValidationService.php`
- `app/Http/Livewire/Settings/RentriSettings.php`
- `resources/views/livewire/settings/rentri-settings.blade.php`
- `tests/Feature/Sprint31/RentriIntegrationTest.php`
- `tests/Feature/Sprint91/RentriSandboxValidationTest.php`
- `docs/VALIDAZIONE-SANDBOX-MASE.md`
- `docs/SPRINT-91-REVIEW-HANDOFF.md`

---

## Sprint 92 — ✅ completato

1. **`.github/workflows/rentri-sandbox-integration.yml`** — `workflow_dispatch` + label `integration-sandbox`; no push default.
2. **Secrets gated** — `RENTRI_SANDBOX_CERT_BASE64` + password; skip esplicito se assenti.
3. **Decode temp p12** — chmod 600, cleanup `always()`, no log cert/password.
4. **Doc** — `VALIDAZIONE-SANDBOX-MASE.md` §8 CI.
5. **Test Sprint 92** — 7 test in `tests/Feature/Sprint92/*` (584+ totali).

### File principali

- `.github/workflows/rentri-sandbox-integration.yml`
- `docs/VALIDAZIONE-SANDBOX-MASE.md` §8
- `tests/Feature/Sprint92/RentriSandboxIntegrationCiTest.php`
- `docs/SPRINT-92-REVIEW-HANDOFF.md`

---

## Sprint 93 — ✅ completato

1. **`RentriSlaMetricsService`** — latency p50/p95, retry trend, dead-letter rate per fir/xfir/registro (7/30 gg).
2. **UI hub `/segreteria/rentri`** — sezione SLA KPI + badge soglie + tabella per tipo.
3. **Config** — `RENTRI_SLA_P95_LATENCY_SECONDS`, `RENTRI_SLA_DEAD_LETTER_RATE_PERCENT`, `RENTRI_SLA_MAX_AVG_RETRY_COUNT`.
4. **Test Sprint 93** — 8 test in `tests/Feature/Sprint93/*` (592+ totali).

### File principali

- `app/Domain/Rentri/RentriSlaMetricsService.php`
- `app/Http/Livewire/Segreteria/Rentri.php`
- `resources/views/livewire/segreteria/rentri.blade.php`
- `tests/Feature/Sprint93/RentriSlaMetricsTest.php`
- `docs/SPRINT-93-REVIEW-HANDOFF.md`

---

## Sprint 94 — ✅ completato

1. **`RentriFirVidimaTransmissionMapper`** — strip `trasporto_id`, `codice_blocco` dal body MASE.
2. **`RentriFirVidimaRequest`** — `body()` MASE-only; `crmAuditPayload()` per audit locale.
3. **`RentriApiClient`** — `crm_audit` in `request_json` transazioni vidima.
4. **Fixture** — `vidima-submit.json` con `crm_excluded_keys`, `example_mase`.
5. **Test Sprint 94** — 7 test in `tests/Feature/Sprint94/*` (599 totali, 4 skipped).

### File principali

- `app/Services/Rentri/RentriFirVidimaTransmissionMapper.php`
- `app/Services/Rentri/Dto/RentriFirVidimaRequest.php`
- `tests/fixtures/rentri/mase/vidima-submit.json`
- `docs/SPRINT-94-AUDIT-NOTES.md`

---

## Sprint 95 — ✅ completato

1. **`MudTelematicoRuntimeModeService`** — stub/live via `MUD_TELEMATICO_STUB` + badge UI.
2. **`MudTelematicoTransmissionService`** — submit async stub (cache) / live HTTP placeholder sandbox.
3. **`MudTelematicoTransmissionMapper`** — body MASE-only; `crm_audit` in `invio_risposta`.
4. **`MudInvioTelematicoService::invia()`** — delega transmission; checklist endpoint live.
5. **UI `MudShow`** — badge, copy dinamica invio, URL canale live.
6. **Test Sprint 95** — 9 test in `tests/Feature/Sprint95/*` (608 totali, 4 skipped).

### File principali

- `app/Domain/Mud/MudTelematicoTransmissionService.php`
- `app/Domain/Mud/MudTelematicoRuntimeModeService.php`
- `app/Domain/Mud/MudTelematicoTransmissionMapper.php`
- `tests/fixtures/mud/mase-invio-submit.json`
- `docs/SPRINT-95-AUDIT-NOTES.md`

---

## Sprint 96 — ✅ completato

1. **`EcommercePaymentRuntimeModeService`** — stub/live via `ECOMMERCE_PAYMENT_STUB`.
2. **`EcommercePaymentGatewayService`** — token stub / Stripe Checkout Session.
3. **`StripeCheckoutClient`** — wrapper `stripe/stripe-php`.
4. **Webhook** — `POST /webhooks/stripe/ecommerce` con verifica firma opzionale.
5. **UI** — badge pagamenti, preflight Stripe, link checkout live.
6. **Test Sprint 96** — 10 test in `tests/Feature/Sprint96/*` (622 totali, 4 skipped).

### File principali

- `app/Domain/Ecommerce/EcommercePaymentGatewayService.php`
- `app/Http/Controllers/Webhooks/StripeEcommerceWebhookController.php`
- `docs/SPRINT-96-AUDIT-NOTES.md`

---

## Sprint 97 — ✅ completato

1. **`TwoFactorEnforcementService`** — config, grace period, ruoli admin/segreteria.
2. **`EnsureTwoFactorEnabled`** — middleware redirect → sicurezza + messaggio IT.
3. **UI** — banner grace + copy enforcement su `SecuritySettingsPage`.
4. **Config** — `TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA`, `TWO_FACTOR_ENFORCE_GRACE_UNTIL`.
5. **Test Sprint 97** — 10 test in `tests/Feature/Sprint97/*` (632 totali, 4 skipped).

### File principali

- `app/Domain/Auth/TwoFactorEnforcementService.php`
- `app/Http/Middleware/EnsureTwoFactorEnabled.php`
- `docs/2FA-PREP-RUNBOOK.md` (Fase 2 enforced)
- `docs/SPRINT-97-AUDIT-NOTES.md`

---

## Sprint 98 — ✅ completato

1. **`TrasportoGpsRuntimeModeService`** — stub/live via `TRASPORTO_GPS_STUB`.
2. **`TrasportoGpsTrackingService`** — poll stub / HTTP provider + persist `gps_last_position`.
3. **Migration** — `gps_last_position` JSON, `gps_tracked_at` su `trasporti`.
4. **UI `TrasportoShow`** — badge GPS, mappa OSM embed, refresh posizione.
5. **Test Sprint 98** — 9 test in `tests/Feature/Sprint98/*` (641 totali, 4 skipped).

### File principali

- `app/Domain/Trasporti/TrasportoGpsTrackingService.php`
- `app/Domain/Trasporti/TrasportoGpsRuntimeModeService.php`
- `tests/fixtures/gps/position-response.json`
- `docs/SPRINT-98-AUDIT-NOTES.md`

---

## Sprint 99 — ✅ completato

1. **`MailTransportRuntimeService`** — stub/live via `NOTIFICATIONS_LIVE` + badge UI.
2. **`NotificationService`** — invio SMTP solo in live; audit log sempre su canale `notifications`.
3. **`NotificationTestMail`** — email di test da hub impostazioni.
4. **UI `NotificationSettingsPage`** — badge, checklist MAIL_*, pulsante «Invia email di test».
5. **Test Sprint 99** — 8 test in `tests/Feature/Sprint99/*` (649 totali, 4 skipped).

### File principali

- `app/Domain/Notifications/MailTransportRuntimeService.php`
- `app/Domain/Notifications/NotificationService.php`
- `app/Mail/NotificationTestMail.php`
- `docs/SPRINT-99-AUDIT-NOTES.md`

---

## Sprint 100 — ✅ completato

1. **`GO-LIVE-OPERATIVO.md`** — checklist env unificata demo/staging/prod (RENTRI, MUD, Stripe, 2FA, GPS, SMTP, CI).
2. **Cross-link** — `GO-LIVE-ENTERPRISE.md`, README, backlog §11.
3. **`CICLO-9-PIANO-STUB.md`** — outline ciclo 9 (sprint 101–110).
4. **Test Sprint 100** — 8 test in `tests/Feature/Sprint100/*` (657 totali, 4 skipped).

### File principali

- `docs/GO-LIVE-OPERATIVO.md`
- `docs/SPRINT-100-REVIEW-HANDOFF.md`
- `docs/CICLO-9-PIANO-STUB.md`
- `tests/Feature/Sprint100/Cycle8ClosureGoLiveTest.php`

---

## Gap target ciclo 8 — tutti risolti ✅

| Area | Sprint | Priorità | Stato |
|------|--------|----------|-------|
| Validazione live cert sandbox | 91 | P0 ops | ✅ |
| Integration CI sandbox | 92 | P1 | ✅ |
| SLA monitoring RENTRI | 93 | P2 | ✅ |
| Payload vidima OpenAPI | 94 | P1 | ✅ |
| MUD telematico live | 95 | P1 normativa | ✅ prep |
| Gateway pagamento | 96 | P2 business | ✅ prep |
| 2FA enforced | 97 | P1 security | ✅ |
| Tracking GPS reale | 98 | P3 | ✅ prep |
| SMTP notifiche live | 99 | P2 ops | ✅ prep |
| Chiusura docs | 100 | — | ✅ |

Gap residui post-ciclo 8 (contratti esterni, pen-test, WAF): vedi [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) §8.

---

## Riferimenti

- [CICLO-7-PIANO.md](CICLO-7-PIANO.md)
- [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §11
- [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md)
