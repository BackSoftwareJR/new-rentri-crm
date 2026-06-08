# RENTRI CRM — Backlog verticale e handoff agenti

**Ultimo aggiornamento:** 4 giugno 2026  
**Sessione:** Ciclo 5 **CHIUSO** ✅ (sprint 51–60)

---

## ✅ Ciclo 5 — Perfezionamento 360° (sprint 51–60) — CHIUSO

**Piano:** [CICLO-5-PIANO-360.md](CICLO-5-PIANO-360.md) · **Go-live:** [GO-LIVE-360.md](GO-LIVE-360.md) · **UAT:** [UAT-UX-360-CHECKLIST.md](UAT-UX-360-CHECKLIST.md)

| Sprint | Feature | Stato |
|--------|---------|-------|
| **51** | Design system + sidebar gruppi + sicurezza quick wins | ✅ |
| **52** | Empty states + route audit + dashboard N+1 + a11y focus | ✅ |
| **53** | Form UX + CSRF/mass assignment + registro paginate | ✅ |
| **54** | Operatore mobile polish + demo isolation test | ✅ |
| **55** | Impostazioni RENTRI wizard + throttle FIR + MUD PDF | ✅ |
| **56** | Dashboard widgets + dark mode + OWASP + Horizon | ✅ |
| **57** | VFU timeline + cert rottamazione + 2FA prep doc | ✅ |
| **58** | Onboarding tour + help contextual + aria-live toast | ✅ |
| **59** | Tablet responsive + print registro + high contrast + k6 | ✅ |
| **60** | UAT UX 360° + GO-LIVE-360 + a11y/lighthouse + chiusura | ✅ |

### Completato Sprint 60

1. **UAT UX 360°** — checklist percorsi segreteria/operatore/RENTRI/palestra.
2. **GO-LIVE-360** — sign-off OWASP + WAF + 2FA consolidato.
3. **A11y axe** — runbook + `scripts/a11y-pages.json` + `axe-smoke.js` stub.
4. **Lighthouse budget** — soglie doc + `lighthouse-budget.json`.
5. **Polish UI** — hint SR ricerca globale, focus help, demo aria-live, copy MUD.
6. **Test Sprint 60** — 7 test in `tests/Feature/Sprint60/*`.

### File principali Sprint 60

- `docs/{UAT-UX-360-CHECKLIST,GO-LIVE-360,A11Y-AUDIT-RUNBOOK,LIGHTHOUSE-BUDGET}.md`
- `scripts/{a11y-pages.json,axe-smoke.js,lighthouse-budget.json}`

### Gap residui post-ciclo 5

Vedi [GO-LIVE-360.md](GO-LIVE-360.md) § Gap — pen-test esterno, 2FA code, WAF infra, cert MASE prod, deploy infra, UAT firmata in sede.

---

## 9. Ciclo 6 — Completamento verticale moduli (sprint 61–75) ✅ CHIUSO

**Piano:** [CICLO-6-PIANO-MODULI-COMPLETI.md](CICLO-6-PIANO-MODULI-COMPLETI.md) · **Go-live:** [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md) · **UAT:** [UAT-CICLO-6-CHECKLIST.md](UAT-CICLO-6-CHECKLIST.md)

| Sprint | Modulo | Stato |
|--------|--------|-------|
| **61** | E-commerce completo (immagini, checkout, stati ordine) | ✅ |
| **62** | Anagrafiche avanzate (P.IVA/CF, alert autorizzazioni) | ✅ |
| **63** | VFU avanzato (allegati, export CSV storico) | ✅ |
| **64** | Magazzino & report (export registro, alert serbatoio) | ✅ |
| **65** | MUD telematico prep (validazione XML, invio stub) | ✅ |
| **66** | Notifiche centralizzate (NotificationService, coda) | ✅ |
| **67** | 2FA TOTP slice (setup QR, challenge login) | ✅ |
| **68** | Report & analytics (dashboard KPI, export mensile) | ✅ |
| **69** | RENTRI prod hardening (checklist cert, stub→live) | ✅ |
| **70** | Trasporti/FIR polish (bulk export, tracking prep) | ✅ |
| **71** | Bonifica operatore (foto→catalogo, checklist pericolosi) | ✅ |
| **72** | Legacy import advanced (sync incrementale, diff) | ✅ |
| **73** | Audit export live (S3, download firmato) | ✅ |
| **74** | Performance & load (k6 autenticati, cache KPI Redis) | ✅ |
| **75** | Chiusura ciclo 6 (UAT, GO-LIVE-CICLO-6) | ✅ |

### Completato Sprint 65

1. **Validazione XML MUD** — schema stub `mud-stub-v1`, build + validate DOM.
2. **Invio telematico stub** — protocollo `MUD-STUB-{anno}-{hash}`, activity log audit.
3. **Checklist pre-invio** — stato completata, payload, righe CER, XML valido.
4. **UI** — MudShow checklist/invio/badge; MudIndex storico con filtri anno/stato.
5. **Test Sprint 65** — 7 test in `tests/Feature/Sprint65/*` (442 test totali).

### Completato Sprint 66

1. **NotificationService hub** — dispatch centralizzato per bonifica, serbatoio, MUD, RENTRI dead-letter.
2. **Template email stub** — Blade per modulo; driver `log` default (no SMTP); coda opzionale via `SendNotificationJob`.
3. **Preferenze evento** — `NotificationPreferenceService` + UI toggle Livewire `segreteria.impostazioni.notifiche`.
4. **Integrazione alert** — refactor `BonificaNotificationService` / `SerbatoioAlertNotificationService`; hook MUD post-invio.
5. **Test Sprint 66** — 7 test in `tests/Feature/Sprint66/*` (449 test totali).

### Completato Sprint 67

1. **Migration 2FA** — `two_factor_secret` (encrypted), `two_factor_confirmed_at` su `users`.
2. **TwoFactorService** — TOTP via `pragmarx/google2fa`, QR SVG, enable/disable.
3. **Login challenge opt-in** — redirect post-password; throttle 5/min; no enforce globale.
4. **UI sicurezza** — `SecuritySettingsPage` con QR setup; sidebar «Sicurezza 2FA».
5. **Policy** — solo admin/segreteria; test Sprint 67: 7 test (456 test totali).

### Completato Sprint 68

1. **DashboardAnalyticsService** — metriche periodo VFU/magazzino/RENTRI/MUD + trend 6 mesi + delta vs precedente.
2. **KpiExportService** — export CSV mensile KPI (`kpi-mensile-6m-*.csv`).
3. **UI dashboard** — filtro periodo, widget analytics, tabella trend, export Livewire.
4. **Policy** — `DashboardReportPolicy` view/export admin/editor/segreteria.
5. **Test Sprint 68** — 7 test in `tests/Feature/Sprint68/*` (463 test totali).

### Completato Sprint 69

1. **RentriProdReadinessService** — checklist 6 voci pre-prod (GO-LIVE RENTRI).
2. **Switch live guidato** — `RentriLiveModeService` + override runtime; activity log audit.
3. **UI step 4** — Passaggio produzione in Impostazioni RENTRI con gate e conferma.
4. **Banner prod/stub** — dashboard + RENTRI + impostazioni se mismatch.
5. **Test Sprint 69** — 7 test in `tests/Feature/Sprint69/*` (470 test totali).

### Completato Sprint 70

1. **FirBulkExportService** — export CSV bulk FIR vidimati/firmati/trasmessi con filtri periodo/stato.
2. **FirIndex polish** — filtri data, stato firmato, export bulk, badge/colonna tracking stub.
3. **TrasportoTrackingPrepService** — timeline GPS/ETA stub con log `trasporto.tracking.stub`.
4. **TrasportoShow** — sezione tracking prep con timeline eventi ed ETA stimata.
5. **Test Sprint 70** — 7 test in `tests/Feature/Sprint70/*` (477 test totali).

### Completato Sprint 71

1. **OperatoreFotoCatalogoService** — foto operatore persistite e collegate a voci catalogo e-commerce.
2. **Ricambi UI** — select prodotto, upload bulk, anteprima thumb foto collegate.
3. **BonificaPericolosiChecklistService** — checklist 4 step (3 manuali + quantità); blocco `completePericolosi`.
4. **BonificaWizard** — sezione checklist con badge N/4; lista bonifica mostra progress checklist.
5. **Policy demo scope** — `linkPhoto`, `saveChecklist`, `advancePericolosi`.
6. **Test Sprint 71** — 7 test in `tests/Feature/Sprint71/*` (484 test totali).

### Completato Sprint 72

1. **LegacyImportSyncService** — sync incrementale CER/anagrafiche/movimenti da fixture con lock cache.
2. **LegacyImportDiffReportService** — diff nuovi/aggiornati/skipped + storico run.
3. **Dashboard sync UI** — ultimo sync, tabella diff, log run recenti.
4. **Job + command** — `LegacyIncrementalSyncJob` (unique), `legacy:sync-incremental`, schedule settimanale.
5. **Policy** — gate `legacy.sync` / `legacy.viewRuns` per admin/editor/segreteria.
6. **Test Sprint 72** — 8 test in `tests/Feature/Sprint72/*` (492 test totali).

### Completato Sprint 73

1. **AuditExportLiveService** — CSV su disk `audit_exports` (local/S3) con SHA-256 e retention purge.
2. **AuditExportDownloadService** — presigned S3 o signed route local + audit trail download.
3. **AuditIndex** — storico export live con checksum e download admin.
4. **Job + command** — `AuditExportScheduledJob` unique, `audit:export-scheduled` reale.
5. **Policy** — `viewExports` / `downloadExport` solo admin.
6. **Test Sprint 73** — 8 test in `tests/Feature/Sprint73/*` (500 test totali).

### Completato Sprint 74

1. **KpiRedisCacheService** — cache KPI dashboard TTL (Redis prod / array in PHPUnit) con meta hit/miss.
2. **DashboardKpiCacheInvalidator** — invalidazione event-driven su save/delete moduli KPI.
3. **UI dashboard** — badge cache hit/miss + pulsante Refresh KPI (policy segreteria/admin).
4. **k6 autenticato** — `scripts/k6-authenticated.js` login cookie + scenari segreteria/operatore.
5. **Horizon prep** — `docs/PERFORMANCE-MONITORING.md` metriche job queue e runbook load test.
6. **Test Sprint 74** — 8 test in `tests/Feature/Sprint74/*` (508 test totali).

### Completato Sprint 75

1. **UAT ciclo 6** — `docs/UAT-CICLO-6-CHECKLIST.md` percorsi E2E sprint 61–74.
2. **GO-LIVE ciclo 6** — `docs/GO-LIVE-CICLO-6.md` sign-off moduli + smoke commands.
3. **Ciclo 6 CHIUSO** — banner in piano moduli, backlog §9, README aggiornato.
4. **Monitoring link** — `PERFORMANCE-MONITORING.md` referenziato in go-live e README.
5. **Test Sprint 75** — 7 test in `tests/Feature/Sprint75/*` (515 test totali).

### Gap post-ciclo 6 (handoff infra)

Gateway pagamento e-commerce, MUD telematico live, 2FA enforced, WAF/pen-test esterno, deploy prod infra, cert MASE 100%, tracking GPS reale, SMTP/push live. Vedi [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md) §6.

---

## 10. Ciclo 7 — Enterprise RENTRI/FIR (sprint 76–90) ✅ CHIUSO

**Piano:** [CICLO-7-PIANO.md](CICLO-7-PIANO.md) · **Audit:** [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md) · **Sign-off:** [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md)

| Sprint | Focus | Stato |
|--------|-------|-------|
| **76** | Audit + remediation P0 (runtime mode, stub offline, COSE alg) | ✅ |
| **77** | REVIEW ONLY — QA fix S76 | ✅ |
| **78** | Blocchi sync + preflight runtime | ✅ |
| **79** | REVIEW ONLY — QA fix S78 | ✅ |
| **80** | Vidima validator service-layer | ✅ |
| **81** | REVIEW ONLY — QA fix S80 | ✅ |
| **82** | Poll xFIR timeout config dedicato | ✅ |
| **83** | REVIEW ONLY — QA fix S82 | ✅ |
| **84** | Contract test payload MASE | ✅ |
| **85** | REVIEW ONLY — QA fix S84 | ✅ |
| **86** | UI copy stub/live sweep | ✅ |
| **87** | REVIEW ONLY — QA fix S86 | ✅ |
| **88** | xFIR payload_firmato COSE audit | ✅ |
| **89** | REVIEW ONLY — QA fix S88 | ✅ |
| **90** | Chiusura ciclo 7 GO-LIVE-ENTERPRISE | ✅ |

### Completato Sprint 76

1. **Audit enterprise** — matrice conformità RENTRI/FIR vs D.D. 143/2023 e demoapi.
2. **P0 runtime mode** — `api_mode` da `RentriRuntimeModeService` (QR, registro, UI trasporto).
3. **P0 stub offline** — health/vidima stub senza cert mTLS in palestra.
4. **P0 COSE alg** — RS256/ES256 per tipo chiave firma xFIR.
5. **Test Sprint 76** — 7 test in `tests/Feature/Sprint76/*` (521 test totali).

### Completato Sprint 77

1. **Review ONLY** — checklist handoff Sprint 76 verificata (test + code review).
2. **Regression** — Sprint 9/34/36/39/42/69/76 + suite 521 passed.
3. **Report** — `docs/SPRINT-77-REVIEW-REPORT.md` con raccomandazione **GO** Sprint 78.

### Completato Sprint 78

1. **P1-1 blocchi sync** — update `progressivo_ultimo` su blocchi MASE già presenti.
2. **P1-2 preflight runtime** — stub/live da `RentriRuntimeModeService` (prod + demo).
3. **Rentri.php** — fallback `api_mode` messaggio registro da runtime.
4. **Test Sprint 78** — 6 test in `tests/Feature/Sprint78/*` (527 test totali).

### Completato Sprint 79

1. **Review ONLY** — checklist P1-1/P1-2 Sprint 78 verificata (6/6).
2. **Regression** — Sprint 32/35/44/76/78 + suite 527 passed.
3. **Report** — `docs/SPRINT-79-REVIEW-REPORT.md` con raccomandazione **GO** Sprint 80.

### Completato Sprint 80

1. **P1-3 vidima validator** — `RentriFirVidimaValidator` + integrazione `RentriFirService::vidima()`.
2. **UI checklist** — TrasportoShow con messaggi IT pre-vidima (parità registro).
3. **Test Sprint 80** — 7 test in `tests/Feature/Sprint80/*` (534 test totali).

### Completato Sprint 82

1. **P1-4 poll xFIR** — config `RENTRI_XFIR_POLL_*` separato da vidima FIR.
2. **Message mapper xFIR** — timeout IT con tentativi/secondi dedicati.
3. **Test Sprint 82** — 6 test in `tests/Feature/Sprint82/*` (540 test totali).

### Completato Sprint 84

1. **P1-5 contract MASE** — fixture vidima/xFIR/registro + contract test DTO.
2. **Enum registro** — CARICO/SCARICO uppercase verificati vs fixture.
3. **Test Sprint 84** — 7 test in `tests/Feature/Sprint84/*` (547 test totali).

### Completato Sprint 86

1. **P2-1 UI stub/live** — badge runtime su TrasportoShow, RENTRI hub, FIR, impostazioni.
2. **Component** — `x-rentri-api-mode-badge` (stub sandbox / RENTRI live / demo offline).
3. **Test Sprint 86** — 8 test in `tests/Feature/Sprint86/*` (555 test totali).

### Completato Sprint 88

1. **Audit COSE payload_firmato** — mapper strip metadati CRM; fixture `xfir-cose-sign1.json`.
2. **Fix M-88-1** — `RentriXfirTrasmissioneRequest` invia solo shape COSE MASE.
3. **Test Sprint 88** — 7 test in `tests/Feature/Sprint88/*` (562 test totali).

### Completato Sprint 89

1. **Review ONLY** — audit COSE payload_firmato 7/7 verificato.
2. **Regression** — Sprint 34/39/84/88 + suite 562 passed.
3. **Report** — `docs/SPRINT-89-REVIEW-REPORT.md` con raccomandazione **GO** Sprint 90.

### Completato Sprint 90

1. **`docs/GO-LIVE-ENTERPRISE.md`** — checklist post-remediation P0–P2 + smoke commands.
2. **Matrice audit finale** — `CICLO-7-ENTERPRISE-AUDIT.md` ✅ P0/P1/P2.
3. **Banner CHIUSO** — CICLO-7-PIANO, README, backlog §10.
4. **Test Sprint 90** — 7 test doc presence + preflight smoke (569 test totali).

### Gap post-ciclo 7

Validazione live RS256/ES256 cert sandbox, integration CI opzionale, SLA monitoring RENTRI, payload vidima OpenAPI completo, eredità cicli 5–6 (WAF, 2FA, deploy infra). Vedi [GO-LIVE-ENTERPRISE.md](GO-LIVE-ENTERPRISE.md) §6.

---

## 11. Ciclo 8 — Validazione operativa reale (sprint 91–100) ✅ CHIUSO

**Piano:** [CICLO-8-PIANO.md](CICLO-8-PIANO.md) · **Go-live:** [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) · **Sandbox:** [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md)

| Sprint | Focus | Stato |
|--------|-------|-------|
| **91** | Validazione live cert sandbox + UI wizard + integration test hardened | ✅ |
| **92** | CI gated integration test sandbox | ✅ |
| **93** | SLA dashboard RENTRI | ✅ |
| **94** | Payload vidima OpenAPI alignment | ✅ |
| **95** | MUD invio telematico live prep | ✅ |
| **96** | Gateway pagamento e-commerce Stripe | ✅ |
| **97** | 2FA enforced admin/segreteria | ✅ |
| **98** | Tracking GPS trasporti provider prep | ✅ |
| **99** | SMTP notifiche live + template | ✅ |
| **100** | Chiusura ciclo 8 GO-LIVE-OPERATIVO | ✅ |

### Completato Sprint 91–94

Vedi [CICLO-8-PIANO.md](CICLO-8-PIANO.md) per dettaglio sprint 91–94.

### Completato Sprint 95–99

1. **MUD telematico** — `MudTelematicoTransmissionService`, badge UI, mapper MASE-only.
2. **Stripe e-commerce** — checkout live prep, webhook, preflight UI.
3. **2FA enforced** — middleware + grace period admin/segreteria.
4. **GPS trasporti** — poll provider, mappa OSM, badge UI.
5. **SMTP notifiche** — `MailTransportRuntimeService`, test email hub.

### Completato Sprint 100

1. **`GO-LIVE-OPERATIVO.md`** — checklist env unificata demo/staging/prod.
2. **Cross-link** enterprise + README + backlog.
3. **`CICLO-9-PIANO-STUB.md`** — outline ciclo 9.
4. **Test Sprint 100** — 8 test doc + preflight smoke (657+ totali, 4 skipped).

### Gap post-ciclo 8 (residui)

Endpoint MUD/GPS contratti reali, Stripe prod, pen-test esterno, WAF attivo — vedi [GO-LIVE-OPERATIVO.md](GO-LIVE-OPERATIVO.md) §8.

**Prossimo:** [CICLO-9-PIANO.md](CICLO-9-PIANO.md) (sprint 101–110).

---

## 12. Ciclo 9 — Produzione e gap infra (sprint 101–110) ✅ CHIUSO

**Piano:** [CICLO-9-PIANO.md](CICLO-9-PIANO.md) · **Go-live:** [GO-LIVE-PRODUZIONE.md](GO-LIVE-PRODUZIONE.md)

| Sprint | Focus | Stato |
|--------|-------|-------|
| **101** | MUD telematico endpoint MASE produzione | ✅ |
| **102** | GPS provider adapter + geofencing | ✅ |
| **103** | Stripe produzione | ✅ |
| **104** | Pen-test OWASP esterno | ✅ |
| **105** | WAF deploy | ✅ |
| **106** | RENTRI prod switch | ✅ |
| **107** | Horizon / SMTP volume | ✅ |
| **108** | HA + backup | ✅ |
| **109** | KPI business v2 | ✅ |
| **110** | Chiusura GO-LIVE-PRODUZIONE | ✅ |

### Completato Sprint 101

1. **`MudTelematicoEndpoints`** — gateway RENTRI sandbox/prod, path `/mud/v1.0/dichiarazioni/*`.
2. **Research** — portale mudtelematico.it (SPID); API CRM gateway-aligned.
3. **UI + probe HEAD** — MudShow submit URL e reachability.
4. **Test Sprint 101** — 7 test (664+ totali).

### Completato Sprint 102

1. **`TrasportoGpsProviderAdapter`** — field map provider JSON (nested support).
2. **Preflight UI** — checklist URL + API key su TrasportoShow.
3. **Geofencing stub** — notifica hub oltre raggio km.
4. **Test Sprint 102** — 8 test (672+ totali).

### Completato Sprint 103

1. **Stripe sandbox/produzione** — preflight sk_live/sk_test, STRIPE_LIVE_MODE, EUR.
2. **Webhook idempotency** — `stripe_webhook_events` + riconciliazione mail.
3. **UI** — badge sandbox/prod, dashboard link, checklist estesa.
4. **Test Sprint 103** — 8 test (680+ totali).

### Completato Sprint 104

1. **Pen-test prep** — `OwaspExternalPrepService`, scope doc, remediation template.
2. **OWASP checklist** — 2FA enforce, Stripe webhook idempotency, MUD/GPS endpoints.
3. **Admin UI** — `/admin/pen-test-prep`.
4. **Test Sprint 104** — 12 test (692+ totali).

### Completato Sprint 105

1. **WAF preflight** — `WafDeploymentPreflightService`, mode off/monitor/block.
2. **Rollout runbook** — 48h monitor → block, SIEM, rollback.
3. **Admin UI** — `/admin/waf-status`.
4. **Test Sprint 105** — 12 test (704+ totali).

### Completato Sprint 106

1. **RENTRI production switch** — `RentriProductionSwitchService`, runbook, CLI dry-run.
2. **UI** — hub + step 4 checklist unificata.
3. **GO-LIVE-RENTRI** — gate post-WAF.
4. **Test Sprint 106** — 10 test (714+ totali).

### Completato Sprint 107

1. **Horizon scaling** — `HorizonScalingPreflightService`, runbook, UI checklist.
2. **SMTP volume** — `SmtpVolumePreflightService`, daily cap config optional.
3. **MONITORING-CICLO-3** — § Horizon + SMTP volume aggiornato.
4. **Test Sprint 107** — 10 test (724+ totali).

### Completato Sprint 108

1. **HA preflight** — `HaBackupPreflightService`, RPO/RTO, backup drill.
2. **Runbook** — restore trimestrale + failover multi-istanza.
3. **Admin UI** — `/admin/ha-status`.
4. **Test Sprint 108** — 10 test (734+ totali).

### Completato Sprint 109

1. **KPI business v2** — `BusinessKpiDashboardService`, widget dashboard, soglie config.
2. **Doc** — `KPI-BUSINESS-DASHBOARD-V2.md`.
3. **Test Sprint 109** — 8 test (742+ totali).

### Completato Sprint 110

1. **`GO-LIVE-PRODUZIONE.md`** — consolidamento ciclo 9, go/no-go, smoke.
2. **Ciclo 9 CHIUSO** — piano + backlog §12.
3. **`CICLO-10-PIANO-STUB.md`** — outline sprint 111–120.
4. **Test Sprint 110** — 8 test (750+ totali).

**Prossimo:** [CICLO-10-PIANO.md](CICLO-10-PIANO.md) (sprint 111–120).

---

## 13. Ciclo 10 — RENTRI cert produzione (sprint 111–120) ✅ CHIUSO

**Piano:** [CICLO-10-PIANO.md](CICLO-10-PIANO.md) · **Sign-off:** [GO-LIVE-CERT-PRODUZIONE.md](GO-LIVE-CERT-PRODUZIONE.md)

| Sprint | Focus | Stato |
|--------|-------|-------|
| **111** | RENTRI cert produzione E2E | ✅ |
| **112** | Post go-live monitoring SLA | ✅ |
| **113** | Pen-test remediation | ✅ |
| **114** | WAF block tuning | ✅ |
| **115** | Mobile app PWA prep | ✅ |
| **116** | GPS provider prod | ✅ |
| **117** | Stripe reconciliation | ✅ |
| **118** | HA failover drill | ✅ |
| **119** | KPI v3 alerts | ✅ |
| **120** | Chiusura GO-LIVE-CERT-PRODUZIONE | ✅ |

### Completato Sprint 111

1. **`RentriProductionCertValidationService`** — E2E api.rentri.gov.it, block demoapi.
2. **UI** — validazione certificato produzione su RentriSettings.
3. **`RentriProductionIntegrationTest`** — gated env, no CI default.
4. **Doc** — `VALIDAZIONE-CERT-PRODUZIONE-RENTRI.md`.
5. **Test Sprint 111** — 10 test (760+ totali).

### Completato Sprint 112

1. **`RentriSlaAlertService`** — P95 + dead-letter vs `RENTRI_SLA_*`; notifiche breach.
2. **`rentri:sla-check --notify`** — cron hourly; JSON per monitoring.
3. **Hub RENTRI** — ultimo check + breach history (activity log).
4. **Doc** — `MONITORING-CICLO-3.md` §4.
5. **Test Sprint 112** — 9 test (769 totali).

### Completato Sprint 113

1. **`PenTestRemediationService`** — CRUD findings vendor JSON.
2. **UI pen-test prep** — registro + export template.
3. **OWASP integration** — scope checklist × findings, gate P0.
4. **Test Sprint 113** — 9 test (778 totali).

### Completato Sprint 114

1. **WAF block tuning** — productionBlockChecklist, findings cross-ref, UI runbook.
2. **Docs** — WAF-STAGING-ROLLOUT, WAF-RULES-PREP §114.
3. **Test Sprint 114** — 9 test (787 totali).

### Completato Sprint 115

1. **OperatoreMobileApiService** — API JSON read-only bonifica/ricambi/vetrina.
2. **PWA** — manifest, SW, offline shell su `/operatore/*`.
3. **Doc** — `OPERATORE-PWA.md`.
4. **Test Sprint 115** — 9 test (796 totali).

### Completato Sprint 116

1. **`TrasportoGpsProductionSwitchService`** — switch stub→live, preset field map, probe, rollback.
2. **`trasporto:gps-switch-check`** — CLI preflight produzione vs stub.
3. **UI hub trasporti** — checklist switch + runbook link.
4. **Doc** — `GPS-PROVIDER-PRODUZIONE-RUNBOOK.md`.
5. **Test Sprint 116** — 11 test (807 totali).

### Completato Sprint 117

1. **`StripeProductionSwitchService`** — switch sandbox→prod, dispute checklist.
2. **`StripeReconciliationReportService`** — reporting CRM vs webhook + CSV.
3. **`StripeDisputeStubService`** — workflow dispute stub.
4. **Doc** — `STRIPE-RECONCILIATION-PRODUZIONE-RUNBOOK.md`.
5. **Test Sprint 117** — 11 test (818 totali).

### Completato Sprint 118

1. **`HaFailoverDrillService`** — drill health/switch/recovery, probe nodi, rollback.
2. **`ha:failover-drill`** — CLI esercitazione documentata.
3. **UI admin** — `/admin/ha-status` sezione failover drill.
4. **Doc** — `HA-FAILOVER-DRILL-RUNBOOK.md`.
5. **Test Sprint 118** — 10 test (828 totali).

### Completato Sprint 119

1. **`BusinessKpiExportService`** — CSV metriche business v3.
2. **`BusinessKpiAlertService`** — alert email soglie + cron.
3. **Doc** — `KPI-BUSINESS-DASHBOARD-V3.md`.
4. **Test Sprint 119** — 10 test (838 totali).

### Completato Sprint 120

1. **`GO-LIVE-CERT-PRODUZIONE.md`** — sign-off certificazione produzione ciclo 10.
2. **Ciclo 10 CHIUSO** — piano, backlog §13, README.
3. **Test Sprint 120** — 9 test chiusura (847 totali).

**Ciclo 10 chiuso.** Baseline test: 847 PHPUnit, 6 skipped.

---

## 14. Post-ciclo 10 — Logging produzione (Sprint 121) ✅

Observability enterprise: log strutturati JSON, correlazione `X-Request-Id` / `trace_id`, persistenza `application_logs`, UI `/admin/logs`, comandi `logs:health` / `logs:purge`.

| # | Deliverable | Stato |
|---|-------------|-------|
| 1 | Canali `rentri`, `security`, `integration`, `business` + `stack_prod` | ✅ |
| 2 | Middleware correlazione + propagazione queue | ✅ |
| 3 | `StructuredLogService` | ✅ |
| 4 | Integrazioni RENTRI API, Stripe, GPS, WAF/preflight, SLA/KPI breach | ✅ |
| 5 | Migration + model `ApplicationLog` | ✅ |
| 6 | UI admin `/admin/logs` + export CSV | ✅ |
| 7 | Artisan `logs:health`, `logs:purge` | ✅ |
| 8 | Docs `LOGGING-PRODUZIONE.md` | ✅ |
| 9 | Test Sprint 121 | ✅ 10 test |

**Baseline post-121:** 857 PHPUnit, 6 skipped.

Vedi: `docs/LOGGING-PRODUZIONE.md`, `docs/SPRINT-121-REVIEW-HANDOFF.md`.

---

## Storico sprint (ciclo 5 dettaglio)

1. **`x-form-field`** — VFU wizard + RentriSettings (label, hint, error IT).
2. **Mass assignment** — `$guarded` Trasporto/Fir/VfuRegistration; `forceFill` service layer.
3. **`UploadValidation`** — PDF + cert p12/pfx con mime whitelist.
4. **CSRF audit** — login, logout sidebar verificati via test HTTP.
5. **Registro movimenti** — empty state + paginazione 25/pag confermata.
6. **Badge WCAG AA** — contrasto colori migliorato (`gestionale.css`).
7. **Bonifica operatore** — header unificato, `x-empty-state` filtrabile.
8. **Test Sprint 53** — 6 test form/security/registro.

### File principali Sprint 53

- `resources/views/components/form-field.blade.php`
- `app/Support/UploadValidation.php`
- `app/Models/{Trasporto,Fir,VfuRegistration}.php`
- `tests/Feature/Sprint53/*`

### Completato Sprint 59

1. **Tablet responsive** — breakpoint 768–1024px, sidebar overlay/collapsible, toggle topbar.
2. **Print registro** — `@media print` + `#seg-registro-print` + pulsante Stampa.
3. **High contrast** — `[data-theme="high-contrast"]` + toggle dedicato; cycle theme esteso.
4. **WAF prep** — `docs/WAF-RULES-PREP.md`.
5. **k6 smoke** — `scripts/k6-smoke.js`.
6. **Audit export scheduling** — comando `audit:export-scheduled` + cron stub + doc prep.
7. **Test Sprint 59** — 10 test in `tests/Feature/Sprint59/*`.

### File principali Sprint 59

- `resources/js/{tablet-sidebar,theme-toggle}.js`
- `resources/css/gestionale.css` (tablet, print, high-contrast)
- `docs/{WAF-RULES-PREP,AUDIT-EXPORT-SCHEDULING-PREP}.md`
- `scripts/k6-smoke.js`
- `routes/console.php`
- `tests/Feature/Sprint59/*`

### Prossimo (Sprint 60)

UAT UX 360°, security sign-off, a11y axe, Lighthouse CI, chiusura ciclo 5.

### Completato Sprint 58

1. **Onboarding tour** — `onboarding-tour.js` (5 step, localStorage, dashboard/RENTRI).
2. **Help contextual** — `x-contextual-help` su dashboard, RENTRI, VFU, trasporti.
3. **Aria-live toast** — `#seg-flash-region` + `aria-atomic` su `x-alert`.
4. **Redis session prep** — `docs/REDIS-SESSION-PREP.md`.
5. **Tracking trasporti stub** — `TrasportoTrackingService` + mappa placeholder.
6. **Test Sprint 58** — 8 test in `tests/Feature/Sprint58/*`.

### File principali Sprint 58

- `resources/js/{onboarding-tour,contextual-help}.js`
- `resources/views/components/contextual-help.blade.php`
- `app/Domain/Trasporti/TrasportoTrackingService.php`
- `docs/REDIS-SESSION-PREP.md`
- `tests/Feature/Sprint58/*`

### Prossimo (Sprint 59)

Responsive tablet, print registro, high contrast, WAF prep, k6 load test.

### Completato Sprint 57

1. **VFU timeline** — `VfuTimelineService` + `x-vfu-timeline` su dettaglio pratica.
2. **Certificato rottamazione** — template HTML migliorato; anteprima iframe + Stampa + PDF.
3. **2FA prep** — `docs/2FA-PREP-RUNBOOK.md` (fasi rollout, no code).
4. **Foto ricambi stub** — bulk upload operatore (max 10) + `uploadPhotos` policy.
5. **Test Sprint 57** — 7 test in `tests/Feature/Sprint57/*`.

### File principali Sprint 57

- `app/Domain/Vfu/VfuTimelineService.php`
- `resources/views/components/vfu-timeline.blade.php`
- `resources/views/pdf/certificato-rottamazione.blade.php`
- `docs/2FA-PREP-RUNBOOK.md`
- `tests/Feature/Sprint57/*`

### Prossimo (Sprint 58)

Onboarding tour, help contextual, live regions toast.

### Completato Sprint 56

1. **Dashboard widgets** — drag-order con localStorage; sezioni KPI riordinabili.
2. **Dark mode prep** — CSS variables `[data-theme="dark"]` + toggle stub topbar.
3. **OWASP checklist** — `docs/OWASP-INTERNAL-CHECKLIST.md` (A01–A10).
4. **Horizon monitor** — link admin in topbar; badge stub per non-admin.
5. **Legacy report UX** — CLI tabellare `--report`; dashboard con badge stato entità.
6. **Test Sprint 56** — 6 test in `tests/Feature/Sprint56/*`.

### File principali Sprint 56

- `resources/js/{dashboard-widget-order,theme-toggle}.js`
- `app/Support/Horizon/HorizonMonitorService.php`
- `resources/views/livewire/segreteria/dashboard.blade.php`
- `docs/OWASP-INTERNAL-CHECKLIST.md`
- `tests/Feature/Sprint56/*`

### Prossimo (Sprint 57)

VFU timeline visuale, certificato rottamazione preview, prep 2FA doc.

### Completato Sprint 55

1. **Wizard RENTRI** — preview scadenza cert mTLS/xFIR; modale dettagli; hint su step wizard.
2. **Throttle FIR** — `FirActionRateLimiter` su vidima/firma (5/min utente).
3. **Export MUD PDF** — `MudPdfExportService` stub PDF minimale.
4. **Modali a11y** — `x-modal` + focus trap keyboard (`modal-focus-trap.js`).
5. **Audit log** — migration indici `created_at` e `log_name+created_at`.
6. **Test Sprint 55** — 8 test in `tests/Feature/Sprint55/*`.

### File principali Sprint 55

- `app/Domain/Rentri/RentriCertPreviewService.php`
- `app/Support/Rentri/FirActionRateLimiter.php`
- `app/Domain/Mud/MudPdfExportService.php`
- `resources/views/components/{cert-expiry-preview,modal}.blade.php`
- `resources/js/modal-focus-trap.js`
- `database/migrations/2026_06_05_120000_add_activity_log_query_indexes.php`
- `tests/Feature/Sprint55/*`

### Prossimo (Sprint 56)

Dashboard widget drag-order, dark mode prep, legacy import report UX.

### Completato Sprint 54

1. **Titoli operatore** — `headerTitle` unico in layout; rimossi `h2.op-section-title` duplicati (ricambi, vetrina, profilo, dashboard).
2. **Bottom nav** — badge bonifica pending + contrasto icone `#636366`; `OperatoreNavBadgeService` + view composer.
3. **Demo isolation** — policy `update` su ordini e-commerce; test cross-write deny ordini/MUD in demo session.
4. **Cache RentriSetting** — `instance()` cached per request via container binding.
5. **E-commerce index** — `x-empty-state` + `x-form-field` su filtri categoria/ricerca.
6. **Flash a11y** — `x-alert` con `role="status"` e `aria-live="polite"`.
7. **Test Sprint 54** — 7 test in `tests/Feature/Sprint54/*`.

### File principali Sprint 54

- `app/Support/Operatore/OperatoreNavBadgeService.php`
- `app/Models/RentriSetting.php`
- `app/Policies/EcommerceOrdinePolicy.php`
- `resources/views/layouts/operatore.blade.php`
- `resources/views/livewire/segreteria/ecommerce/index.blade.php`
- `resources/views/components/alert.blade.php`
- `tests/Feature/Sprint54/*`

### Prossimo (Sprint 55)

Wizard impostazioni RENTRI step, throttle FIR vidima/firma, export MUD PDF.

### Completato Sprint 53

1. **`x-empty-state`** — VFU, FIR, trasporti index con messaggi IT e CTA.
2. **Route audit** — Livewire update route con middleware `auth`; test allowlist.
3. **`BonificaPolicy`** — Gate operatore-only; authorize su Bonifica/BonificaWizard.
4. **Dashboard KPI** — `vfuCounts()` query unica GROUP BY (fix N+1).
5. **A11y** — `:focus-visible` su bottoni, sidebar, input.
6. **Test Sprint 52** — 8 test (empty state, route audit, KPI query, bonifica policy).

### File principali Sprint 52

- `resources/views/components/empty-state.blade.php`
- `app/Policies/BonificaPolicy.php`
- `app/Domain/Dashboard/DashboardKpiService.php`
- `app/Providers/AppServiceProvider.php` (Livewire auth route)
- `tests/Feature/Sprint52/*`

### Prossimo (Sprint 53)

Form field component, audit CSRF/mass assignment, paginazione registro movimenti, contrasto badge WCAG AA.

### Completato Sprint 51

1. **Componenti Blade** — `x-btn`, `x-alert`, `x-page-header`; flash con `warning`.
2. **Sidebar** — gruppi Operativo/RENTRI/Amministrazione, tooltip, badge «Palestra ON».
3. **Dashboard** — azioni rapide, KPI priorità RENTRI condizionale.
4. **Header pagina** — rentri, magazzino, trasporto show unificati.
5. **Sicurezza** — login `throttle:5,1`; Gate `demo.toggle`; cert `extensions:p12,pfx`; policy view su FIR trasporto; rate limit `trasmetti` (3/min).
6. **Mobile operatore** — touch target 44px su filtri bottom nav.
7. **Docs** — `CICLO-5-PIANO-360.md`, `UX-GUIDELINES.md`.
8. **Test Sprint 51** — sidebar 200, demo deny operatore, login throttle, flash Livewire.

### File principali Sprint 51

- `resources/views/components/{btn,alert,page-header}.blade.php`
- `resources/views/components/sidebar-nav.blade.php`
- `resources/css/gestionale.css`
- `app/Providers/AppServiceProvider.php`, `routes/web.php`
- `tests/Feature/Sprint51/*`

### Prossimo (Sprint 52)

Empty/error states su VFU/FIR/trasporti index; audit middleware route; eager load dashboard KPI; focus ring a11y.

---

## ✅ Ciclo 4 — Palestra operativa (sprint 46–50) — **CHIUSO**

| Sprint | Feature | Stato |
|--------|---------|-------|
| **46** | Toggle sessione + sandbox UI | ✅ |
| **47** | Isolamento verticale demo/prod | ✅ |
| **48** | Preset multi-operatore + UX | ✅ |
| **49** | Playwright + CI produzione | ✅ |
| **50** | UAT runbook + chiusura docs | ✅ |

**Piano:** [CICLO-4-PIANO.md](CICLO-4-PIANO.md) · **Checklist:** [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md)

**Gap residui operativi:** UAT firmato in sede, cert MASE produzione, deploy infra, pen-test esterno.

---

Vedi sezione storica sotto.

---

## 🔄 Ciclo 3 — Piattaforma demo + gap produzione (sprint 36–45) — **CHIUSO ✅**

| Sprint | Feature | Stato |
|--------|---------|-------|
| **36** | **Fondamenta piattaforma demo** | ✅ |
| **37** | **Demo seed + walkthrough E2E** | ✅ |
| **38** | **Validazione XSD xFIR ministeriale** | ✅ |
| **39** | **Invio xFIR firmato MASE** | ✅ |
| **40** | **Job retry MASE** | ✅ |
| **41** | **Conformità registro** | ✅ |
| **42** | **Conformità FIR** | ✅ |
| **43** | **Isolamento trasporti demo** | ✅ |
| **44** | **Deploy demo CI/CD** | ✅ |
| **45** | **Hardening + chiusura ciclo 3** | ✅ |

Documentazione: [GO-LIVE-CICLO-3.md](GO-LIVE-CICLO-3.md) · [CICLO-3-PIANO-COMPLETO.md](CICLO-3-PIANO-COMPLETO.md) · [DEMO-DEPLOY.md](DEMO-DEPLOY.md) · [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md)

---

## 🔄 Ciclo 3 — storico handoff (sprint 36+)

## ✅ Ciclo 2 — Produzione RENTRI/FIR — CHIUSO (sprint 31–35)

| Sprint | Feature | Stato |
|--------|---------|-------|
| **31** | **Account RENTRI + client API mTLS + test connessione** | ✅ |
| **32** | **Vidima FIR reale end-to-end (async status + sync blocchi)** | ✅ |
| **33** | **Trasmissione registro reale + payload conforme** | ✅ |
| **34** | **Firma COSE certificato dominio + validazione xFIR** | ✅ |
| **35** | **Go-live RENTRI prod + runbook conformità** | ✅ |

Documentazione: [GO-LIVE-RENTRI.md](GO-LIVE-RENTRI.md) · [SPRINT-31](SPRINT-31-RENTRI-PRODUZIONE.md) … [SPRINT-35](SPRINT-35-RENTRI-PRODUZIONE.md)

### Gap residui (manutenzione, non bloccano ciclo)

- Validazione XSD xFIR completa ministeriale
- Invio payload xFIR firmato a endpoint dedicato (se distinto)
- Pen test / audit sicurezza produzione
- Job queue retry/backoff automatico su indisponibilità MASE

---

## 🔄 Ciclo 2 — storico sprint (sprint 31+)

## 1. Mappa completamento per area (% ciclo 2)

| Area | % | Stato |
|------|---|--------|
| RENTRI onboarding / account | 90% | Wizard, mTLS, firma cert, preflight operator |
| RENTRI API client | 90% | mTLS, vidima + registro async, polling, integrazione test |
| FIR digitali | 90% | Vidima + firma COSE + download + validazione subset |
| RENTRI trasmissione registro | 85% | Payload MASE + async + protocollo |
| Deploy readiness RENTRI | 85% | GO-LIVE-RENTRI, preflight esteso, runbook |

---

## 3. Completato in questa sessione (Sprint 35)

1. **`PreflightService`** — check cert firma xFIR, dati operatore, stub firma in production.
2. **`docs/GO-LIVE-RENTRI.md`** — variabili prod, smoke E2E, runbook MASE/rotazione/rollback.
3. **`RentriIntegrationTest`** — health + blocchi + codifiche (opzionale).
4. **README** — tabella ciclo 2, config RENTRI aggiornata.
5. **4 test Sprint 35** preflight go-live.

### File principali

- `app/Domain/Deploy/PreflightService.php`
- `docs/GO-LIVE-RENTRI.md`, `docs/SPRINT-35-RENTRI-PRODUZIONE.md`
- `tests/Feature/Sprint35/*`

---

## 3b. Completato Sprint 34 (storico)

1. **`RentriFirmaCertificateService`** — storage certificato firma remota distinto da mTLS.
2. **`RentriFirSigningService`** — build xFIR → validate → COSE_Sign1 → persist.
3. **UI** — upload certificato firma in impostazioni; firma/download su dettaglio trasporto.
4. **6 test Sprint 34** (validator, signing stub, HTTP Livewire).

### File principali

- `app/Services/Rentri/RentriFirSigningService.php`, `RentriXfirCoseSigner.php`, `RentriFirmaCertificateService.php`
- `app/Http/Livewire/Settings/RentriSettings.php`, `Trasporti/TrasportoShow.php`
- `docs/SPRINT-34-RENTRI-PRODUZIONE.md`
- `tests/Feature/Sprint34/*`

---

## 3b. Completato Sprint 33 (storico)

1. **`RentriRegistryService::transmit()`** — submit → poll status → result via `RentriRegistroTrasmissioneRequest`.
2. **Mapping MASE** — `RegistroMovimento` → `codice_cer`, `tipo_movimento`, `quantita_kg`, `data_movimento`, `riferimento_interno`.
3. **`RentriApiClient`** — `submitRegistroTrasmissione`, `waitRegistroTrasmissioneResult`; stub async compatibile test.
4. **UI `/segreteria/rentri`** — copy live, messaggio protocollo stub/live.
5. **4 test Sprint 33** + fix test Sprint 9.

### File principali

- `app/Services/Rentri/RentriRegistryService.php`, `RentriApiClient.php`, `Dto/RentriRegistroTrasmissioneRequest.php`
- `app/Http/Livewire/Segreteria/Rentri.php`
- `docs/SPRINT-33-RENTRI-PRODUZIONE.md`
- `tests/Feature/Sprint33/*`

---

## 3b. Completato Sprint 32 (storico)

1. **`RentriFirService::vidima()`** — submit → poll status → result via `RentriFirVidimaRequest` e path `/vidimazione-formulari/v1.0/{codice_blocco}`.
2. **`RentriApiClient`** — `submitFirVidima`, `waitFirVidimaResult`, `fetchFirBlocchi`; stub async compatibile test.
3. **`RentriFirBlocchiSync`** — import blocchi da GET vidimazione-formulari; UI «Sincronizza da RENTRI».
4. **UI trasporto** — protocollo, transazione_id, QR da `qr_payload`; messaggio vidima stub/live.
5. **6 test Sprint 32** + fix test Sprint 9/31.

### File principali

- `app/Services/Rentri/RentriFirService.php`, `RentriApiClient.php`, `RentriFirBlocchiSync.php`
- `app/Http/Livewire/Segreteria/Fir/FirBlocchiIndex.php`, `Trasporti/TrasportoShow.php`
- `docs/SPRINT-32-RENTRI-PRODUZIONE.md`
- `tests/Feature/Sprint32/*`

---

## 3b. Completato Sprint 31 (storico)

1. **Ricerca** — doc `SPRINT-31-RENTRI-PRODUZIONE.md` (fonti MASE, demoapi, mTLS, gap, endpoint v1.0).
2. **`RentriCertificateService`** — storage PKCS#12 sicuro, scadenza openssl, mTLS Guzzle options.
3. **`RentriApiClient`** — base URL demoapi/api.rentri.gov.it, health live via blocchi FIR, codifiche `/codifiche/v1.0/cer`, errori IT, correlation id.
4. **`RentriConnectionStatusService` + UI** — badge stub/live, stato connessione, CF operatore, test connessione (health + codifiche).
5. **`RentriFirVidimaRequest`** — adapter preparatorio Sprint 32.
6. **6 test Sprint 31** + aggiornamento test Sprint 7/9/10.

### File principali

- `app/Services/Rentri/RentriApiClient.php`, `RentriCertificateService.php`, `RentriEndpoints.php`
- `app/Domain/Rentri/RentriConnectionStatusService.php`
- `app/Http/Livewire/Settings/RentriSettings.php`
- `docs/SPRINT-31-RENTRI-PRODUZIONE.md`
- `tests/Feature/Sprint31/*`

---

## 4. Verifica build/test

```bash
cd new-rentri-crm
php artisan test
php artisan test --filter=Sprint35
php artisan rentri:preflight
npm run build
```

---

## 6. Ciclo 3 — Piattaforma demo + gap produzione (sprint 36–45)

**Piano completo:** [CICLO-3-PIANO-COMPLETO.md](CICLO-3-PIANO-COMPLETO.md)

| Sprint | Feature | Stato |
|--------|---------|-------|
| **36** | **Fondamenta piattaforma demo** (`APP_DEMO_MODE`, `is_demo`, banner, API sandbox/offline, `rentri:demo-reset`) | ✅ |
| **37** | **Demo seed + walkthrough E2E** (`rentri:demo-seed`, card dashboard, `DEMO-DEPLOY.md`) | ✅ |
| **38** | **XSD xFIR ministeriale** (schema repo, validator XSD, errori IT UI) | ✅ |
| **39** | **Invio xFIR firmato MASE** (endpoint dedicato, protocollo, storico) | ✅ |
| **40** | **Job retry MASE** (queue backoff, dead-letter, UI stato job) | ✅ |
| **41** | **Conformità registro** (checklist ministeriale, lock movimento, audit export) | ✅ |
| **42** | **Conformità FIR** (edge vidima, firma pre-vidima, QR payload spec) | ✅ |
| **43** | **Isolamento trasporti demo** (scope svuotamenti, seed demo, test cross-ref) | ✅ |
| **44** | **Deploy demo CI/CD** (pipeline `.env.demo`, health, preflight variant) | ✅ |
| **45** | **Hardening + chiusura ciclo 3** (pen-test checklist, GO-LIVE-CICLO-3) | ✅ |

### Completato Sprint 45

1. **`docs/SECURITY-CHECKLIST-DEMO-PROD.md`** — pen-test checklist `is_demo`, API sandbox, sessioni.
2. **`Cycle3MonitoringService` + `rentri:monitor`** — health `/up`, alert dead-letter/retry, misconfig demo/prod.
3. **Dashboard KPI** — dead-letter e retry pianificati in `/segreteria`.
4. **`docs/GO-LIVE-CICLO-3.md`** + **`docs/MONITORING-CICLO-3.md`** — runbook chiusura ciclo 3.
5. **12 test Sprint 45**; README e backlog ciclo 3 chiuso.

### File principali Sprint 45

- `app/Domain/Deploy/Cycle3MonitoringService.php`, `app/Console/Commands/RentriMonitorCommand.php`
- `docs/GO-LIVE-CICLO-3.md`, `docs/SECURITY-CHECKLIST-DEMO-PROD.md`, `docs/MONITORING-CICLO-3.md`
- `tests/Feature/Sprint45/*`

### Prossimo lavoro (post ciclo 3 — manutenzione)

- Pipeline CI **produzione** (infra team)
- Pen test OWASP esterno / WAF
- Load test MASE, pagamenti e-commerce

---

## 7. Ciclo 4 — Palestra operativa + sandbox integrato (sprint 46–50) ✅ CHIUSO

**Piano:** [CICLO-4-PIANO.md](CICLO-4-PIANO.md) · **Guida:** [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) · **UAT:** [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md)

| Sprint | Feature | Stato |
|--------|---------|-------|
| **46** | **Toggle sidebar + RENTRI sandbox UI** | ✅ |
| **47** | **Revisione verticale modulo-per-modulo** | ✅ |
| **48** | **Preset multi-operatore + UX + go-live ciclo 4** | ✅ |
| **49** | **Smoke E2E + magazzino stock + CI prod** | ✅ |
| **50** | **Chiusura ciclo 4 + go-live RENTRI** | ✅ |

### Completato Sprint 50

1. **`UAT-FORMAZIONE-PALESTRA.md`** — runbook sessione 90 min + checklist firmabile.
2. **`RUNBOOK-POST-DEPLOY.md`** — preflight, `rentri:monitor`, dead-letter, escalation.
3. **GO-LIVE-RENTRI** §5–6 ciclo 4 completo; **GO-LIVE-CICLO-4** marcato CHIUSO.
4. **README + CICLO-4-PIANO** banner CHIUSO + gap residui §9.
5. **Test Sprint 50** docs presence + monitor smoke.

### File principali Sprint 50

- `docs/UAT-FORMAZIONE-PALESTRA.md`, `docs/RUNBOOK-POST-DEPLOY.md`
- `docs/GO-LIVE-CICLO-4.md`, `docs/CICLO-4-PIANO.md`, `docs/GO-LIVE-RENTRI.md`
- `tests/Feature/Sprint50/*`

### Gap residui (operativi)

- UAT formazione firmato in sede
- Certificati MASE produzione + stub disabilitati
- Deploy prod infra team, pen-test esterno

### Completato Sprint 49

1. **Playwright smoke** — `tests/e2e/palestra-smoke.spec.ts` (toggle → walkthrough → preset multi-operatore); `@playwright/test`.
2. **Magazzino stock demo** — `resolveGiacenzaKg()` da movimenti scoped; skip mutazioni prod su `magazzino_rifiuti`.
3. **CI produzione** — `.github/workflows/production.yml` (PHPUnit + preflight + Playwright, no deploy secrets).
4. **4 test Sprint 49** + `GO-LIVE-CICLO-4.md` aggiornato.

### File principali Sprint 49

- `playwright.config.ts`, `tests/e2e/palestra-smoke.spec.ts`, `scripts/e2e-serve.sh`
- `app/Support/Demo/DemoTrainingScope.php` (giacenza virtuale)
- `.github/workflows/production.yml`
- `tests/Feature/Sprint49/*`

### Completato Sprint 49

1. **Preset multi-operatore** — profili `default`/`sede_nord`/`sede_sud` in config; selector UI Impostazioni RENTRI demo.
2. **UX walkthrough** — progress bar, hint cert scadenza, deep link `?step=`, link app operatore.
3. **Scope e-commerce/MUD** — `is_demo` + `HasDemoScope`; policy fix; audit log filtrato `demo_mode`.
4. **`docs/GO-LIVE-CICLO-4.md`** — checklist chiusura palestra operativa.
5. **13 test Sprint 48** in `tests/Feature/Sprint48/*`.

### File principali Sprint 48

- `config/demo.php` (`operators`), `app/Domain/Demo/DemoRentriPresetService.php`
- `database/migrations/2026_06_05_110000_add_demo_scope_to_ecommerce_and_mud.php`
- `resources/views/components/demo-rentri-walkthrough.blade.php`
- `docs/GO-LIVE-CICLO-4.md`
- `tests/Feature/Sprint48/*`

### Completato Sprint 47

1. **Isolamento moduli formazione** — `is_demo` su anagrafiche/VFU; `DemoTrainingScope` per CER magazzino; `DemoContext::scopedModels()` esteso.
2. **Policy cross-demo** — `EnforcesDemoScope` su policy FIR/registro/trasporti/magazzino/RENTRI.
3. **Credenziali demo avanzate** — `note_operatore`, badge scadenza cert, walkthrough 6 step.
4. **E2E Livewire** — `PalestraOperativaE2eTest` con `Http::fake` MASE.
5. **7 test Sprint 47** + FAQ `PALESTRA-OPERATIVA.md`.

### File principali Sprint 47

- `app/Policies/Concerns/EnforcesDemoScope.php`, `app/Support/Demo/DemoTrainingScope.php`
- `database/migrations/2026_06_05_100000_add_demo_scope_to_training_modules.php`
- `app/Domain/Demo/DemoSeedService.php` (walkthrough 6 step)
- `resources/views/livewire/settings/rentri-settings.blade.php`
- `tests/Feature/Sprint47/*`

### Completato Sprint 46

1. **`DemoModeSessionService`** — activate/deactivate, guardrail production, activity log.
2. **`DemoContext`** — `isActive()` = deploy OR session; sandbox forzata in session demo.
3. **Sidebar Livewire `DemoModeToggle`** — modale conferma, link impostazioni RENTRI demo.
4. **`DemoRentriPresetService`** — preset sandbox da UI + test connessione.
5. **`RentriApiClient`** — session demo: live sandbox se cert presente, altrimenti stub.
6. **Test Sprint 46** + docs aggiornati.

### File principali Sprint 46

- `app/Domain/Demo/DemoModeSessionService.php`, `DemoRentriPresetService.php`
- `app/Support/Demo/DemoContext.php`, `app/Http/Middleware/EnsureDemoModeScope.php`
- `app/Http/Livewire/Segreteria/DemoModeToggle.php`
- `docs/CICLO-4-PIANO.md`, `docs/PALESTRA-OPERATIVA.md`
- `tests/Feature/Sprint46/*`

---

### Completato Sprint 39

1. **`RentriApiClient::submitXfirFirmato()`** — POST `/vidimazione-formulari/v1.0/xfir/trasmissione`, poll async status/result.
2. **`RentriXfirTransmissionService`** — post-firma submit → protocollo su FIR + `rentri_transazioni` tipo `xfir`.
3. **UI trasporto** — badge invio MASE, «Invia xFIR a MASE», link storico API; filtro `xfir`.
4. **Stub async** + test live `Http::fake`; demo `RENTRI_DEMO_NO_HTTP` rispettato.
5. **6 test Sprint 39**.

### File principali Sprint 39

- `app/Services/Rentri/RentriXfirTransmissionService.php`, `Dto/RentriXfirTrasmissioneRequest.php`
- `app/Services/Rentri/RentriApiClient.php`, `RentriEndpoints.php`
- `database/migrations/2026_06_04_210000_add_xfir_transmission_fields_to_firs_table.php`
- `tests/Feature/Sprint39/*`

### Completato Sprint 40

1. **`RetryRentriTransazioneJob`** — exponential backoff `RENTRI_RETRY_*`, max tentativi, dead-letter (`dead_letter_at`).
2. **`RentriTransazioneRetryService` + `RentriTransazioneRetryExecutor`** — auto-dispatch su fallimento API fir/xfir/registro (408, 429, 5xx); `replayTransazione()` riusa la stessa riga.
3. **Migration** — `retry_count`, `next_retry_at`, `dead_letter_at` su `rentri_transazioni`.
4. **UI storico API** — KPI retry/dead-letter, badge colonna Retry, dettaglio con «Riprova ora».
5. **9 test Sprint 40**; `.env.example` aggiornato.

### File principali Sprint 40

- `app/Jobs/RetryRentriTransazioneJob.php`
- `app/Domain/Rentri/RentriTransazioneRetryService.php`, `RentriTransazioneRetryExecutor.php`
- `app/Services/Rentri/RentriApiClient.php` — `replayTransazione()`, auto-schedule retry
- `database/migrations/2026_06_04_220000_add_retry_fields_to_rentri_transazioni_table.php`
- `resources/views/livewire/segreteria/rentri/transazioni-index.blade.php`, `transazione-show.blade.php`
- `tests/Feature/Sprint40/*`

### Completato Sprint 41

1. **`RentriRegistroConformitaValidator`** — checklist campi obbligatori payload registro (CF, num_iscr_sito, cert mTLS, periodo, movimenti) con messaggi IT.
2. **Lock movimento** — `RegistroMovimento` blocca update/delete post-trasmissione; policy `update`/`delete` rispettano `isLocked()`.
3. **`RentriRegistroAuditExportService`** — export audit trail JSON/CSV (protocollo, periodo, movimenti, transazione RENTRI).
4. **UI `/segreteria/rentri`** — checklist pre-invio, pulsante disabilitato se KO, export audit nello storico.
5. **8 test Sprint 41**.

### File principali Sprint 41

- `app/Domain/Rentri/RentriRegistroConformitaValidator.php`, `RentriRegistroAuditExportService.php`
- `app/Domain/Registro/Exceptions/RegistroMovimentoLockedException.php`
- `app/Models/RegistroMovimento.php`, `app/Services/Rentri/RentriRegistryService.php`
- `resources/views/livewire/segreteria/rentri.blade.php`
- `tests/Feature/Sprint41/*`

### Completato Sprint 42

1. **Edge vidima** — blocco FIR esaurito (`RENTRI_FIR_PROGRESSIVO_MAX`), timeout poll async con messaggi IT.
2. **Firma xFIR blocked** — `signBlockReason()` + validazione payload QR ministeriale v1.0.
3. **`RentriFirQrPayloadValidator` + `RentriFirQrPayloadBuilder`** — spec QR post-vidima.
4. **UI trasporto/blocchi** — blocker vidima, motivo firma bloccata, badge disponibilità.
5. **8 test Sprint 42**.

### File principali Sprint 42

- `app/Services/Rentri/RentriFirService.php`, `RentriFirSigningService.php`
- `app/Services/Rentri/RentriFirQrPayloadValidator.php`, `RentriFirQrPayloadBuilder.php`, `RentriFirVidimaMessageMapper.php`
- `app/Domain/Fir/FirBloccoService.php`, `app/Models/FirBlocco.php`
- `tests/Feature/Sprint42/*`

### Completato Sprint 43

1. **`HasDemoScope` su `MagazzinoSvuotamento`** — migration `is_demo`, global scope demo/prod.
2. **FK guard** — `Trasporto` verifica `is_demo` coerente con svuotamento collegato.
3. **`DemoSeedService`** — seed crea svuotamento demo + trasporto via `creaDaSvuotamento()`.
4. **`DemoResetService`** — elimina anche `magazzino_svuotamenti` demo.
5. **7 test Sprint 43** + aggiornamento test Sprint 37.

### File principali Sprint 43

- `app/Models/MagazzinoSvuotamento.php`, `app/Models/Trasporto.php`
- `app/Domain/Demo/DemoSeedService.php`, `DemoResetService.php`
- `database/migrations/2026_06_04_230000_add_is_demo_to_magazzino_svuotamenti_table.php`
- `tests/Feature/Sprint43/*`

### Completato Sprint 44

1. **`.env.demo.example`** — template committato per CI/staging (`APP_DEMO_MODE=true`, stub RENTRI).
2. **`.github/workflows/demo-staging.yml`** — migrate, seed, `rentri:demo-seed --fresh`, preflight demo, test suite.
3. **`DemoPreflightService`** + `rentri:preflight --demo [--require-seed]` — health check demo (sandbox, seed, `/up`).
4. **`docs/DEMO-DEPLOY.md`** — bootstrap CI/staging documentato.
5. **9 test Sprint 44**.

### File principali Sprint 44

- `app/Domain/Deploy/DemoPreflightService.php`, `app/Console/Commands/PreflightCommand.php`
- `.env.demo.example`, `.github/workflows/demo-staging.yml`
- `tests/Feature/Sprint44/*`

---

### Completato Sprint 38 (storico)

1. **Schema XSD** — `resources/schemas/rentri/xfir-v1.0.xsd` (MASE v1.0).
2. **`RentriXfirValidator`** — campi obbligatori + validazione XSD via libxml; `RentriXfirXmlSerializer`.
3. **`RentriXfirValidationException`** + mapper messaggi italiani; UI trasporto con elenco errori XSD.
4. **Fixture XML** valid/invalid + **8 test Sprint 38**; Sprint 34 aggiornato.

### File principali Sprint 38

- `resources/schemas/rentri/xfir-v1.0.xsd`
- `app/Services/Rentri/RentriXfirValidator.php`, `RentriXfirXmlSerializer.php`, `RentriXfirValidationMessageMapper.php`
- `app/Services/Rentri/Exceptions/RentriXfirValidationException.php`
- `tests/fixtures/xfir/*`, `tests/Feature/Sprint38/*`

---

### Completato Sprint 37 (storico)

1. **`DemoSeedService` + `rentri:demo-seed`** — settings sandbox, blocco DEMO-BLK-001, trasporto preparazione, movimento bozza; idempotente; `--fresh`.
2. **Card dashboard** «Prova flusso RENTRI» con 5 step linkati (solo `APP_DEMO_MODE`).
3. **`docs/DEMO-DEPLOY.md`** — pattern `.env.demo`, bootstrap, comandi reset/seed.
4. **7 test Sprint 37** (seed, idempotenza, fresh, card demo/prod).

### File principali Sprint 37

- `app/Domain/Demo/DemoSeedService.php`, `app/Console/Commands/DemoSeedCommand.php`
- `resources/views/components/demo-rentri-walkthrough.blade.php`
- `app/Http/Livewire/Segreteria/Dashboard.php`, `resources/views/livewire/segreteria/dashboard.blade.php`
- `docs/DEMO-DEPLOY.md`
- `tests/Feature/Sprint37/*`

### Prossimo subagente (Sprint 38)

Implementare **validazione XSD xFIR ministeriale completa**: schema MASE in repo, `RentriXfirValidator` esteso, messaggi errore IT in UI dettaglio trasporto/firma, fixture XML validi/invalidi + test Feature Sprint 38.

---

### Completato Sprint 36 (storico)

1. **`config/demo.php`** — `APP_DEMO_MODE`, `RENTRI_DEMO_FORCE_SANDBOX`, `RENTRI_DEMO_NO_HTTP`.
2. **`HasDemoScope`** — global scope + guard cross-write su 7 tabelle RENTRI-critical.
3. **`RentriApiClient`** — forza sandbox in demo; `offline_no_http`; blocca api.rentri.gov.it.
4. **UI** — banner persistente; sezione «Ambiente di prova» in impostazioni RENTRI.
5. **`rentri:demo-reset`** — elimina solo `is_demo=true`.
6. **12 test Sprint 36** + piano ciclo 3 documentato.

### File principali Sprint 36

- `app/Support/Demo/`, `app/Models/Concerns/HasDemoScope.php`, `app/Domain/Demo/DemoResetService.php`
- `config/demo.php`, `database/migrations/2026_06_04_200000_add_is_demo_to_rentri_critical_tables.php`
- `resources/views/components/demo-banner.blade.php`
- `docs/CICLO-3-PIANO-COMPLETO.md`
- `tests/Feature/Sprint36/*`

### Prossimo subagente (Sprint 37)

Implementare **`rentri:demo-seed`**: fixture minime (RentriSetting demo, FirBlocco, Trasporto, RegistroMovimento) + card dashboard «Prova flusso RENTRI» con link step-by-step; documentare pattern deploy `.env.demo`; test Feature seed + smoke HTTP dashboard.

---

*Handoff ciclo 3 — Sprint 36 — 4 giugno 2026.*
