# Ciclo 5 — Perfezionamento 360° CRM RENTRI autodemolitore

**Sprint 51–60** · Partenza: ciclo 4 chiuso (333 test PHPUnit + E2E palestra)

---

## 1. Obiettivi ciclo 5

| # | Obiettivo | Esito atteso |
|---|-----------|--------------|
| 1 | **UX coerente** | Design system unificato, sidebar raggruppata, header pagina, feedback flash uniforme |
| 2 | **Sicurezza operativa** | Rate limit login/RENTRI, Gate demo, policy su azioni Livewire, validazione upload cert |
| 3 | **Accessibilità** | Touch target mobile, contrasto, aria-label, focus visibile |
| 4 | **Performance** | Lazy load tabelle pesanti, cache KPI dashboard, ottimizzazione query N+1 |
| 5 | **Moduli residui** | Bonifica operatore, legacy import polish, e-commerce checkout, audit export |

---

## 2. Audit sintetico (Fase A)

### UI / UX

- Layout segreteria: sidebar + `gestionale.css` con token `--color-primary`, classi `seg-*`.
- Gap pre-Sprint 51: header inline duplicati, flash non uniforme, sidebar piatta senza gruppi.
- Operatore mobile: bottom nav presente; touch target sotto 44px su filtri.

### Sicurezza

- Login senza throttle → **fix Sprint 51** (`throttle:5,1`).
- `DemoModeToggle` senza Gate → **fix Sprint 51** (`demo.toggle`).
- Upload cert senza `extensions:p12,pfx` → **fix Sprint 51**.
- `TrasportoShow` vidima/firma senza re-check `view` → **fix Sprint 51**.
- RENTRI `trasmetti` senza rate limit → **fix Sprint 51** (3/min per utente).

---

## 3. Tabella sprint 51–60

| Sprint | UX / Grafica | Sicurezza | Accessibilità | Performance | Moduli residui | Stato |
|--------|--------------|-----------|---------------|-------------|----------------|-------|
| **51** | Design system (`x-btn`, `x-alert`, `x-page-header`); sidebar gruppi; dashboard KPI; header rentri/magazzino/trasporto; flash uniforme; mobile touch 44px | Login throttle; Gate demo; cert extensions; policy trasporto; rate limit trasmetti | Tooltip sidebar; aria-current attivo | — | — | ✅ |
| **52** | Empty states VFU/FIR/trasporti; audit route; dashboard N+1; focus ring | Login throttle; Gate demo; cert extensions; policy view su FIR; rate limit trasmetti | Focus ring coerente | Eager load dashboard KPI | — | ✅ |
| **53** | Form Livewire: label/help/error coerenti; validazione inline IT | CSRF audit form upload; mass assignment review modelli critici | Contrasto WCAG AA su badge stati | Paginazione server-side registro | Bonifica operatore UX | ✅ |
| **54** | Operatore: unificare titoli pagina; bottom nav icone + badge | Demo isolation test estesi; upload MIME whitelist generale | Screen reader annunci flash Livewire | Cache config RentriSetting | E-commerce catalog polish | ✅ |
| **55** | Impostazioni RENTRI wizard step; preview cert scadenza | Throttle azioni FIR vidima/firma; IP allowlist admin (opz.) | Keyboard nav modali | Query audit log indexed | MUD export PDF | ✅ |
| **56** | Dashboard widget drag-order (localStorage); dark mode prep | Pen-test checklist OWASP interno | Reduced motion CSS | Horizon queue monitor UI | Legacy import report UX | ✅ |
| **57** | VFU dettaglio timeline visuale; stampa certificato preview | 2FA prep (documentazione) | Form error summary top | CDN asset versioning | Ricambi operatore foto bulk | ✅ |
| **58** | Onboarding first-run tour; help contextual | Secrets rotation runbook | Live regions per toast | Redis session (staging) | Trasporti mappa tracking (stub) | ✅ |
| **59** | Responsive tablet segreteria; print stylesheet registro | WAF rules documentate | High contrast mode toggle | Load test script k6 | Audit export scheduling | ✅ |
| **60** | UAT UX 360° checklist; polish finale | Security sign-off interno | A11y audit axe | Lighthouse CI budget | Chiusura ciclo 5 + GO-LIVE-360 | ✅ |

---

## 4. Sprint 51 — completato

### Deliverable

1. Componenti Blade riusabili: `x-btn`, `x-alert`, `x-page-header`.
2. Sidebar segreteria: gruppi Operativo / RENTRI / Amministrazione, badge «Palestra ON», tooltip.
3. Dashboard: barra azioni rapide, blocco KPI priorità RENTRI condizionale.
4. Header unificato su rentri, magazzino, trasporto show.
5. Flash partial con `warning` + `x-alert`.
6. CSS: empty state, sidebar groups, dashboard priority, operatore touch 44px.
7. Sicurezza: login throttle, Gate `demo.toggle`, cert `extensions:p12,pfx`, policy view su FIR, rate limit trasmetti.
8. Docs: `UX-GUIDELINES.md`, questo piano.
9. Test Sprint 51 in `tests/Feature/Sprint51/*`.

### File principali

- `resources/views/components/{btn,alert,page-header}.blade.php`
- `resources/views/components/sidebar-nav.blade.php`
- `resources/css/gestionale.css`
- `app/Providers/AppServiceProvider.php` (Gate demo)
- `routes/web.php` (login throttle)
- `tests/Feature/Sprint51/UxSecurityQuickWinsTest.php`

---

## 5. Istruzione Sprint 52 — ✅ completato

1. **`x-empty-state`** — componente + applicato a VFU/FIR/trasporti index.
2. **Route audit** — Livewire update con `auth`; test allowlist route pubbliche.
3. **`BonificaPolicy`** — Gate `bonifica.viewAny` / `bonifica.perform`; authorize su Bonifica/BonificaWizard.
4. **`DashboardKpiService`** — `vfuCounts()` singola query GROUP BY (N+1 fix).
5. **CSS `:focus-visible`** — bottoni, sidebar, input.
6. **Test Sprint 52** — 8 test in `tests/Feature/Sprint52/*`.

---

## 6. Istruzione Sprint 53 — ✅ completato

1. **`x-form-field`** — label, hint, error; applicato a VFU wizard e RentriSettings.
2. **Mass assignment** — `$guarded` su Trasporto, Fir, VfuRegistration; `forceFill` nei service.
3. **Upload** — `UploadValidation` (PDF + cert PKCS#12) con mime whitelist.
4. **CSRF** — audit login/logout/sidebar; test HTTP.
5. **Registro** — empty state + paginazione verificata (25/pag).
6. **Badge WCAG AA** — contrasto migliorato su tutte le varianti.
7. **Bonifica operatore** — titolo solo in header layout; `x-empty-state`.
8. **Test Sprint 53** — 6 test in `tests/Feature/Sprint53/*`.

---

## 7. Istruzione Sprint 54 — ✅ completato

**Focus:** operatore mobile polish + demo isolation + cache RentriSetting.

1. Unificare titoli pagine operatore (ricambi, vetrina, profilo, dashboard) via layout `headerTitle`.
2. Bottom nav: badge contatori (veicoli bonifica) + icone contrasto verificato.
3. Test isolamento demo estesi su e-commerce ordini e MUD update cross-mode.
4. Cache `RentriSetting::instance()` per request (container binding).
5. E-commerce index: empty state + form-field su filtri.
6. Livewire flash: `role="status"` + `aria-live` su `x-alert`.
7. Test Sprint 54: 7 test in `tests/Feature/Sprint54/*`.

---

## 8. Istruzione Sprint 55 — ✅ completato

**Focus:** wizard impostazioni RENTRI + throttle FIR + export MUD.

1. Wizard RENTRI: preview scadenza cert mTLS/firma + modale dettagli.
2. Throttle vidima/firma FIR — `FirActionRateLimiter` (5/min per utente).
3. Export MUD PDF stub (`MudPdfExportService`).
4. Keyboard nav modali — `x-modal` + focus trap JS.
5. Indici query audit log (`created_at`, `log_name+created_at`).
6. Test Sprint 55: 8 test in `tests/Feature/Sprint55/*`.

---

## 9. Istruzione Sprint 56 — ✅ completato

**Focus:** dashboard widget drag-order + dark mode prep + legacy import UX.

1. Dashboard widget drag-order con persistenza localStorage.
2. Dark mode prep — CSS variables e toggle stub in layout segreteria.
3. Pen-test checklist OWASP interno (`docs/OWASP-INTERNAL-CHECKLIST.md`).
4. Horizon queue monitor link/stub in topbar (`HorizonMonitorService`).
5. Legacy import report UX — CLI tabellare + dashboard con stato per entità.
6. Test Sprint 56: 6 test in `tests/Feature/Sprint56/*`.

---

## 10. Istruzione Sprint 57 — ✅ completato

**Focus:** VFU timeline visuale + certificato rottamazione + 2FA prep.

1. Timeline stati pratica su VfuShow (`VfuTimelineService` + `x-vfu-timeline`).
2. Preview/stampa certificato rottamazione migliorata (HTML + pulsante Stampa).
3. `docs/2FA-PREP-RUNBOOK.md` — prep 2FA senza implementazione.
4. Ricambi operatore — upload foto bulk stub + policy `uploadPhotos`.
5. Test Sprint 57: 7 test in `tests/Feature/Sprint57/*`.

---

## 11. Istruzione Sprint 58 — ✅ completato

**Focus:** onboarding first-run + help contextual + live regions toast.

1. Tour onboarding first-run (`onboarding-tour.js`, localStorage, 5 step dashboard/RENTRI).
2. Help contextual su pagine chiave (`x-contextual-help` + dialog stub).
3. Live regions per toast — `#seg-flash-region` + `aria-atomic` su `x-alert`.
4. Redis session prep — `docs/REDIS-SESSION-PREP.md` (staging only).
5. Trasporti mappa tracking stub — `TrasportoTrackingService` + placeholder su TrasportoShow.
6. Test Sprint 58: 8 test in `tests/Feature/Sprint58/*`.

---

## 12. Istruzione Sprint 59 — ✅ completato

**Focus:** responsive tablet + print registro + high contrast + audit export prep.

1. Layout tablet 768–1024px — sidebar collapsible/overlay (`tablet-sidebar.js`, `data-seg-tablet-sidebar`).
2. Print stylesheet registro movimenti — `#seg-registro-print`, `.seg-no-print`, pulsante Stampa.
3. High contrast toggle — estende theme cycle (`light` → `dark` → `high-contrast`) + `#seg-contrast-toggle`.
4. WAF prep — `docs/WAF-RULES-PREP.md`.
5. k6 smoke — `scripts/k6-smoke.js`.
6. Audit export scheduling — `audit:export-scheduled` stub + Schedule + `docs/AUDIT-EXPORT-SCHEDULING-PREP.md`.
7. Test Sprint 59: 10 test in `tests/Feature/Sprint59/*`.

---

## 13. Istruzione Sprint 60 — ✅ completato (CICLO 5 CHIUSO)

**Focus:** UAT UX 360° + security sign-off + polish finale + GO-LIVE-360.

1. `docs/UAT-UX-360-CHECKLIST.md` — percorsi segreteria/operatore/RENTRI/palestra.
2. `docs/GO-LIVE-360.md` — sign-off OWASP + WAF + 2FA consolidato.
3. `docs/A11Y-AUDIT-RUNBOOK.md` + `scripts/{a11y-pages.json,axe-smoke.js}`.
4. `docs/LIGHTHOUSE-BUDGET.md` + `scripts/lighthouse-budget.json`.
5. Polish UI: hint ricerca globale SR, focus help, demo banner aria-live, copy MUD.
6. README + backlog ciclo 5 **CHIUSO**.
7. Test Sprint 60: 7 test in `tests/Feature/Sprint60/*`.

---

## 14. Gap residui post-ciclo 5 (handoff produzione)

- UAT formazione firmato + pen-test OWASP esterno
- Certificati MASE produzione reali
- Deploy prod infra team
- 2FA e WAF (Sprint 55–59 prep)
