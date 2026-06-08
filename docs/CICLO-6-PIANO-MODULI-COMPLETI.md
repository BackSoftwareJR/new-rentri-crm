# Ciclo 6 — Completamento verticale moduli CRM RENTRI

**Sprint 61–75** · **CHIUSO ✅** · Partenza: ciclo 5 chiuso (405 test PHPUnit, GO-LIVE-360)

**Chiusura:** Sprint 75 — [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md) · [UAT-CICLO-6-CHECKLIST.md](UAT-CICLO-6-CHECKLIST.md) · 508+ test PHPUnit

---

## 1. Obiettivo ciclo 6

Portare ogni modulo core da **MVP/stub** a **flusso operativo end-to-end** documentato e testato, mantenendo isolamento demo e convenzioni ciclo 5.

---

## 2. Inventario moduli (Fase A)

| Modulo | % stimato | Gap funzionale/normativo | File chiave | Dipendenze |
|--------|-----------|--------------------------|-------------|------------|
| **Dashboard/KPI** | **95%** ✅ S74 | Cache Redis KPI live | `KpiRedisCacheService`, `DashboardKpiService` | Tutti moduli |
| **Anagrafiche** | **85%** ✅ S62 | Import bulk, sync RENTRI soggetti | `AnagraficaService`, `AuthorizationAlertService` | Trasporti, FIR |
| **Codici CER** | 90% | Import bulk, revisione normativa | `CodiciCerIndex`, `CodiceCerForm` | Magazzino |
| **VFU & Bonifica** | **88%** ✅ S63 | Email agenzia reale, push notifiche operatore | `VfuShow`, `VfuDocumentoService`, `VfuStoricoExportService` | Magazzino, E-commerce |
| **Magazzino & Registro** | **90%** ✅ S64 | Alert soglie email reale | `RegistroMovimentiExportService`, `SerbatoioAlertService` | RENTRI trasmissione |
| **Trasporti & FIR** | **88%** ✅ S70 | Tracking GPS reale, FIR cartaceo legacy | `FirBulkExportService`, `TrasportoTrackingPrepService` | RENTRI xFIR |
| **RENTRI** | **85%** ✅ S69 | Cert MASE prod live 100% | `RentriProdReadinessService`, `RentriLiveModeService` | Impostazioni, certificati |
| **MUD** | **75%** ✅ S65 | Invio telematico ministeriale live | `MudInvioTelematicoService`, `MudXmlValidationService` | Registro |
| **E-commerce** | **90%** ✅ S61 | Gateway pagamento reale (post ciclo 6) | `EcommerceService`, `EcommerceCheckoutService` | VFU, demo scope |
| **Operatore mobile** | **78%** ✅ S71 | Push notifiche | `OperatoreFotoCatalogoService`, `BonificaPericolosiChecklistService` | E-commerce |
| **Palestra/Demo** | 90% | Reset selettivo per modulo | `DemoSeedService`, `DemoContext` | Tutti |
| **Admin/Audit** | **78%** ✅ S73 | WAF infra | `AuditExportLiveService`, `AuditIndex` | Horizon |
| **Sicurezza** | **65%** ✅ S67 | 2FA enforced, WAF infra | `TwoFactorService`, `2FA-PREP-RUNBOOK` | pragmarx/google2fa |
| **Notifiche** | **70%** ✅ S66 | SMTP live, push operatore | `NotificationService`, template Blade | Mail, Horizon |

---

## 3. Tabella sprint 61–75

| Sprint | Modulo verticale | Deliverable end-to-end | Stato |
|--------|------------------|------------------------|-------|
| **61** | **E-commerce completo** | Immagini prodotto, stati ordine, checkout token stub, ordini recenti | ✅ |
| **62** | **Anagrafiche avanzate** | Validazione P.IVA/CF, dashboard alert autorizzazioni scadenza | ✅ |
| **63** | **VFU avanzato** | Allegati documenti pratica, export CSV storico stati | ✅ |
| **64** | **Magazzino & report** | Export registro CSV, alert soglie serbatoio UI+email stub | ✅ |
| **65** | **MUD telematico prep** | Validazione export XML, invio stub ministeriale | ✅ |
| **66** | **Notifiche centralizzate** | `NotificationService`, template email, coda Horizon | ✅ |
| **67** | **2FA TOTP (slice)** | Migrazione colonne, setup QR, challenge login opt-in | ✅ |
| **68** | **Report & analytics** | Dashboard analytics, export mensile KPI | ✅ |
| **69** | **RENTRI prod hardening** | Checklist cert reali, switch stub→live guidato | ✅ |
| **70** | **Trasporti/FIR polish** | Tracking integrazione prep, FIR bulk export | ✅ |
| **71** | **Bonifica operatore** | Foto→catalogo link, checklist fase pericolosi | ✅ |
| **72** | **Legacy import advanced** | Sync incrementale, report diff | ✅ |
| **73** | **Audit export live** | S3 upload export, download firmato admin | ✅ |
| **74** | **Performance & load** | k6 scenari autenticati, cache KPI Redis | ✅ |
| **75** | **Chiusura ciclo 6** | UAT moduli, GO-LIVE-CICLO-6, backlog handoff | ✅ |

---

## 4. Sprint 61 — ✅ completato (E-commerce)

### Deliverable

1. Migration `immagine_path`, `checkout_token`, stati ordine estesi.
2. `EcommerceProdottoImmagineService` — upload JPG/PNG/WebP (max 2 MB).
3. `EcommerceCheckoutService` — bozza → pagamento_in_attesa → confermato / annullato + ripristino giacenza.
4. Enum `OrdineEcommerceStato`: Bozza, PagamentoInAttesa, Confermato, Annullato.
5. UI: thumb catalogo, upload immagine dettaglio, checkout token su ordine, elenco ordini recenti.
6. Policy `uploadImage`, `checkout`, `annulla` con demo scope.
7. Test Sprint 61: 6 test in `tests/Feature/Sprint61/*`.

### File principali

- `database/migrations/2026_06_06_100000_ecommerce_sprint61_completeness.php`
- `app/Domain/Ecommerce/{EcommerceCheckoutService,EcommerceProdottoImmagineService}.php`
- `app/Http/Livewire/Segreteria/Ecommerce/*`
- `resources/views/livewire/segreteria/ecommerce/*`

---

## 5. Sprint 62 — ✅ completato (Anagrafiche avanzate)

### Deliverable

1. `ItalianFiscalValidator` — checksum P.IVA (11 cifre, prefisso IT) e codice fiscale (regex + carattere controllo, omocodia).
2. Regole `ValidPartitaIva` / `ValidCodiceFiscale` in `StoreAnagraficaRequest::baseRules()` (Livewire `AnagraficaForm`).
3. `AuthorizationAlertService` — conteggio e elenco autorizzazioni scadute / in scadenza ≤15 gg (solo trasportatori e impianti con trasporti).
4. Widget dashboard + banner index + alert dettaglio show + badge «Scaduta» in elenco.
5. Test Sprint 62: 10 test in `tests/Feature/Sprint62/*`.

### File principali

- `app/Support/ItalianFiscalValidator.php`
- `app/Rules/{ValidPartitaIva,ValidCodiceFiscale}.php`
- `app/Domain/Anagrafiche/AuthorizationAlertService.php`
- `app/Http/Livewire/Segreteria/{Dashboard,Anagrafiche/*}.php`
- `resources/views/livewire/segreteria/{dashboard,anagrafiche/*}.blade.php`

---

## 6. Sprint 63 — ✅ completato (VFU avanzato)

### Deliverable

1. Migration `vfu_documenti` + model `VfuDocumento` (tipo, path, `uploaded_by`).
2. `VfuDocumentoService` — upload/list/download/delete con `UploadValidation::vfuAllegatoRules()`.
3. `VfuDocumentoPolicy` + `exportStorico` su `VfuRegistrationPolicy`.
4. `VfuStoricoExportService` — CSV timeline stati da `VfuTimelineService` (filtri search/stato/data).
5. UI: sezione allegati su `VfuShow`, export CSV su index/show.
6. Test Sprint 63: 6 test in `tests/Feature/Sprint63/*`.

### File principali

- `database/migrations/2026_06_07_100000_create_vfu_documenti_table.php`
- `app/Domain/Vfu/{VfuDocumentoService,VfuStoricoExportService}.php`
- `app/Models/VfuDocumento.php`, `app/Policies/VfuDocumentoPolicy.php`
- `app/Http/Livewire/Segreteria/Vfu/{VfuShow,VfuIndex}.php`

---

## 7. Sprint 64 — ✅ completato (Magazzino & report)

### Deliverable

1. `RegistroMovimentiExportService` — export CSV movimenti (filtri periodo, CER, tipo, ricerca).
2. `SerbatoioAlertService` — conteggio serbatoi in attenzione (≥70%) e soglia superata (demo scope).
3. `SerbatoioAlertNotificationService` — stub notifica su `Log::info` (no SMTP) dopo carico manuale in soglia.
4. UI: export CSV su `RegistroMovimentiIndex`, banner alert su `SerbatoioShow`, elenco alert in dashboard KPI.
5. Policy `exportAny` su `RegistroMovimentoPolicy`.
6. Test Sprint 64: 8 test in `tests/Feature/Sprint64/*`.

### File principali

- `app/Domain/Registro/RegistroMovimentiExportService.php`
- `app/Domain/Magazzino/{SerbatoioAlertService,SerbatoioAlertNotificationService}.php`
- `app/Http/Livewire/Segreteria/Magazzino/{RegistroMovimentiIndex,SerbatoioShow}.php`
- `app/Http/Livewire/Segreteria/Dashboard.php`

---

## 8. Sprint 65 — ✅ completato (MUD telematico prep)

### Deliverable

1. Migration `inviata_at`, `invio_protocollo`, `invio_risposta` + stato `Inviata`.
2. `MudXmlValidationService` — build/validate XML stub (`mud-stub-v1`).
3. `MudInvioTelematicoService` — checklist pre-invio, invio stub con protocollo + activity log.
4. UI `MudShow`: checklist, invio stub, badge protocollo; export XML.
5. UI `MudIndex`: storico invii, filtri anno/stato, KPI inviate.
6. Policy `invioTelematico`; test Sprint 65: 7 test in `tests/Feature/Sprint65/*`.

### File principali

- `database/migrations/2026_06_08_100000_mud_sprint65_invio_telematico.php`
- `app/Domain/Mud/{MudXmlValidationService,MudInvioTelematicoService}.php`
- `app/Http/Livewire/Segreteria/Mud/{MudShow,MudIndex}.php`
- `resources/views/livewire/segreteria/mud/*`

---

## 9. Sprint 66 — ✅ completato (Notifiche centralizzate)

### Deliverable

1. `NotificationService` + `NotificationPreferenceService` — hub dispatch per modulo (bonifica, magazzino, MUD, RENTRI).
2. Enum `NotificationEvent`; config `notifications.php` (driver log/mail, queue opzionale, canale `notifications`).
3. Template Blade stub: serbatoio, MUD invio, RENTRI dead-letter (+ bonifica esistente).
4. Refactor `BonificaNotificationService`, `SerbatoioAlertNotificationService`; hook MUD post-invio stub.
5. UI `NotificationSettingsPage` — toggle per evento; route `segreteria.impostazioni.notifiche`.
6. `SendNotificationJob` per coda Horizon opzionale; test Sprint 66: 7 test in `tests/Feature/Sprint66/*`.

### File principali

- `app/Domain/Notifications/{NotificationService,NotificationPreferenceService}.php`
- `app/Enums/NotificationEvent.php`, `config/notifications.php`
- `app/Mail/{SerbatoioSogliaAlertMail,MudInvioTelematicoMail,RentriDeadLetterMail}.php`
- `app/Http/Livewire/Settings/NotificationSettingsPage.php`
- `resources/views/mail/*`, `resources/views/livewire/settings/notification-settings.blade.php`

---

## 10. Sprint 67 — ✅ completato (2FA TOTP opt-in)

### Deliverable

1. Migration `two_factor_secret` (encrypted), `two_factor_confirmed_at` su `users`.
2. `TwoFactorService` — secret TOTP, QR SVG (`pragmarx/google2fa` + `bacon/bacon-qr-code`), verify/enable/disable.
3. Login challenge opt-in — redirect post-password se 2FA attivo; throttle 5/min; nessun enforce globale.
4. UI `SecuritySettingsPage` — setup QR + conferma/disattiva; route `segreteria.impostazioni.sicurezza`.
5. Policy `TwoFactorSettingsPolicy` — solo admin/segreteria; operatore/editor esclusi.
6. Test Sprint 67: 7 test in `tests/Feature/Sprint67/*`.

### File principali

- `database/migrations/2026_06_09_100000_add_two_factor_to_users_table.php`
- `app/Domain/Auth/TwoFactorService.php`, `config/two-factor.php`
- `app/Http/Controllers/Auth/TwoFactorChallengeController.php`
- `app/Http/Livewire/Settings/SecuritySettingsPage.php`
- `resources/views/{auth/two-factor-challenge,livewire/settings/security-settings}.blade.php`

---

## 11. Sprint 68 — ✅ completato (Report & analytics)

### Deliverable

1. `DashboardAnalyticsService` — metriche VFU/magazzino/RENTRI/MUD per periodo; trend 6 mesi; delta vs periodo precedente.
2. `KpiExportService` — export CSV mensile KPI (stub schedulabile Horizon).
3. UI dashboard — filtro periodo Livewire, widget confronto, tabella trend, pulsante export.
4. `DashboardReportPolicy` — view/export per admin/editor/segreteria.
5. Test Sprint 68: 7 test in `tests/Feature/Sprint68/*`.

### File principali

- `app/Domain/Dashboard/{DashboardAnalyticsService,KpiExportService}.php`
- `app/Http/Livewire/Segreteria/Dashboard.php`
- `resources/views/livewire/segreteria/dashboard.blade.php`
- `app/Policies/DashboardReportPolicy.php`

---

## 12. Sprint 69 — ✅ completato (RENTRI prod hardening)

### Deliverable

1. `RentriProdReadinessService` — checklist pre-prod (6 voci: ambiente, cert mTLS/firma, operatore, onboarding, health).
2. `RentriRuntimeModeService` + `RentriLiveModeService` — override runtime stub→live via DB (`live_mode_enabled_at`).
3. UI `RentriSettings` step 4 «Passaggio produzione» con gate checklist e conferma wire.
4. Banner `<x-rentri-prod-stub-banner />` su dashboard e pagina RENTRI se prod + stub attivo.
5. Activity log audit su enable/revert live; test Sprint 69: 7 test in `tests/Feature/Sprint69/*`.

### File principali

- `database/migrations/2026_06_10_100000_rentri_sprint69_live_mode.php`
- `app/Domain/Rentri/{RentriProdReadinessService,RentriRuntimeModeService,RentriLiveModeService}.php`
- `resources/views/components/rentri-prod-stub-banner.blade.php`
- `app/Http/Livewire/Settings/RentriSettings.php` (step 4)

---

## 13. Sprint 70 — ✅ completato (Trasporti/FIR polish)

**Focus:** tracking integrazione prep e export bulk FIR.

1. `FirBulkExportService` — export CSV multiplo FIR vidimati/firmati/trasmessi per periodo/stato.
2. UI `FirIndex` — filtri data + stato (firmato), export bulk, badge tracking stub + colonna GPS.
3. `TrasportoTrackingPrepService` — placeholder integrazione GPS/ETA (stub log, no API esterna).
4. UI `TrasportoShow` — sezione tracking prep con timeline eventi stub + ETA.
5. Test Sprint 70: 7 test in `tests/Feature/Sprint70/*` (477 test totali).

### File principali

- `app/Domain/Fir/{FirBulkExportService,FirService}.php`
- `app/Domain/Trasporti/TrasportoTrackingPrepService.php`
- `app/Http/Livewire/Segreteria/Fir/FirIndex.php`
- `app/Http/Livewire/Segreteria/Trasporti/TrasportoShow.php`
- `resources/views/livewire/segreteria/{fir/index,trasporti/show}.blade.php`

---

## 14. Sprint 71 — ✅ completato (Bonifica operatore)

**Focus:** collegamento foto ricambi al catalogo e checklist fase pericolosi.

1. `OperatoreFotoCatalogoService` — upload foto operatore collegate a `EcommerceProdotto` (tabella `ecommerce_prodotto_foto_operatore`).
2. UI `Ricambi` — selezione ricambio, upload bulk, preview foto collegate per voce catalogo.
3. `BonificaPericolosiChecklistService` — 3 step manuali + quantità auto; blocco avanzamento fase pericolosi.
4. UI `BonificaWizard` + lista — badge checklist N/4, checkbox step, validazione prima di `confirmPericolosi`.
5. Policy `linkPhoto`, `bonifica.saveChecklist`, `bonifica.advancePericolosi` con demo scope.
6. Test Sprint 71: 7 test in `tests/Feature/Sprint71/*` (484 test totali).

### File principali

- `database/migrations/2026_06_11_100000_sprint71_bonifica_operatore.php`
- `app/Domain/{Ecommerce/OperatoreFotoCatalogoService,Bonifica/BonificaPericolosiChecklistService}.php`
- `app/Http/Livewire/Operatore/{Ricambi,BonificaWizard}.php`
- `resources/views/livewire/operatore/{ricambi,bonifica-wizard,bonifica}.blade.php`

---

## 15. Sprint 72 — ✅ completato (Legacy import advanced)

**Focus:** sync incrementale dati legacy e report diff post-import.

1. `LegacyImportSyncService` — sync incrementale `codici_cer` → `anagrafiche` → `movimenti` da fixture; lock idempotente.
2. `LegacyImportService::syncAnagrafiche/syncCodiciCer` — insert nuovi + update campi se record esistente.
3. `LegacyImportDiffReportService` — diff nuovi/aggiornati/skipped/errori per entità e run log.
4. UI dashboard — ultimo sync, tabella diff, log 5 run recenti; gate `legacy.viewRuns`.
5. Job `LegacyIncrementalSyncJob` (`ShouldBeUnique`) + command `legacy:sync-incremental` + schedule settimanale.
6. Test Sprint 72: 8 test in `tests/Feature/Sprint72/*` (492 test totali).

### File principali

- `database/migrations/2026_06_12_100000_sprint72_legacy_import_sync.php`
- `app/Domain/Legacy/{LegacyImportSyncService,LegacyImportDiffReportService}.php`
- `app/Jobs/LegacyIncrementalSyncJob.php`
- `app/Console/Commands/LegacySyncIncrementalCommand.php`
- `resources/views/livewire/segreteria/dashboard.blade.php` (sezione sync)

---

## 16. Sprint 73 — ✅ completato (Audit export live)

**Focus:** export audit log su storage live e download firmato admin.

1. `AuditExportLiveService` — CSV activity log su disk configurabile (`audit_exports` local / S3) + checksum SHA-256.
2. `AuditExportDownloadService` — URL presigned S3 o signed route local + audit trail download.
3. UI `AuditIndex` — storico export live, checksum, pulsante download admin.
4. Job `AuditExportScheduledJob` (`ShouldBeUnique`) + command `audit:export-scheduled` reale + retention purge.
5. Config `config/audit.php` + disk `audit_exports` in `filesystems.php`.
6. Test Sprint 73: 8 test in `tests/Feature/Sprint73/*` (500 test totali).

### File principali

- `database/migrations/2026_06_13_100000_sprint73_audit_export_runs.php`
- `app/Domain/Audit/{AuditExportLiveService,AuditExportDownloadService}.php`
- `app/Jobs/AuditExportScheduledJob.php`
- `app/Console/Commands/AuditExportScheduledCommand.php`
- `app/Http/Controllers/Admin/AuditExportDownloadController.php`

---

## 17. Sprint 74 — ✅ completato (Performance & load)

**Focus:** scenari k6 autenticati e cache KPI Redis.

1. `KpiRedisCacheService` — cache KPI dashboard (Redis prod / array in test) con TTL configurabile e meta hit/miss.
2. `DashboardKpiCacheInvalidator` — invalidazione event-driven su VFU, registro, RENTRI transazioni, MUD, e-commerce, CER.
3. Script `scripts/k6-authenticated.js` — login CSRF cookie + scenari `segreteriaFlow` / `operatoreFlow`.
4. UI dashboard — badge «KPI cache: hit/miss» + pulsante «Refresh KPI» (policy `refreshKpi`).
5. `docs/PERFORMANCE-MONITORING.md` — KPI cache, k6, Horizon prep.
6. Config `config/dashboard.php` — `KPI_CACHE_ENABLED`, `KPI_CACHE_STORE`, `KPI_CACHE_TTL`.
7. Test Sprint 74: 8 test in `tests/Feature/Sprint74/*` (508 test totali).

### File principali

- `app/Domain/Dashboard/{KpiRedisCacheService,DashboardKpiCacheInvalidator}.php`
- `config/dashboard.php`
- `app/Http/Livewire/Segreteria/Dashboard.php`
- `resources/views/livewire/segreteria/dashboard.blade.php`
- `scripts/k6-authenticated.js`
- `docs/PERFORMANCE-MONITORING.md`

---

## 18. Sprint 75 — ✅ completato (Chiusura ciclo 6)

**Focus:** UAT moduli verticali, sign-off GO-LIVE-CICLO-6, handoff backlog post-ciclo.

1. **`docs/UAT-CICLO-6-CHECKLIST.md`** — percorsi E2E sprint 61–74 per modulo verticale.
2. **`docs/GO-LIVE-CICLO-6.md`** — sign-off moduli, smoke commands, gap residui post-tutti-moduli.
3. **Backlog §9** — ciclo 6 marcato CHIUSO ✅ in `RENTRI_VERTICAL_BACKLOG.md`.
4. **README** — sezione ciclo 6 + link `PERFORMANCE-MONITORING.md`.
5. **Test Sprint 75** — 7 test in `tests/Feature/Sprint75/*` (515 test totali).

### File principali

- `docs/UAT-CICLO-6-CHECKLIST.md`
- `docs/GO-LIVE-CICLO-6.md`
- `docs/CICLO-6-PIANO-MODULI-COMPLETI.md` (banner CHIUSO)
- `tests/Feature/Sprint75/Cycle6ClosureGoLiveTest.php`

---

## 19. Gap residui post-ciclo 6 (target)

- Gateway pagamento e-commerce reale (Stripe/Nexi)
- MUD invio telematico ministeriale live
- 2FA enforced admin/segreteria
- WAF + pen-test esterno
- Deploy produzione infra

---

## Riferimenti

- [GO-LIVE-360.md](GO-LIVE-360.md) — baseline ciclo 5
- [RENTRI_VERTICAL_BACKLOG.md](RENTRI_VERTICAL_BACKLOG.md) §9
- [UX-GUIDELINES.md](UX-GUIDELINES.md)
