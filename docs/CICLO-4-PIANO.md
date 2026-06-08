# Ciclo 4 — Palestra operativa + RENTRI sandbox integrato

> ## ✅ CHIUSO — Sprint 50 (giugno 2026)
>
> Ciclo 4 **chiuso a livello codice, test, CI e documentazione**.  
> Gap residui operativi: UAT firmato in sede, certificati MASE produzione reali, pen-test OWASP esterno, deploy prod infra team.

**Sprint 46–50** · Partenza: ciclo 3 chiuso (sprint 36–45), [GO-LIVE-CICLO-3.md](GO-LIVE-CICLO-3.md)

---

## 1. Obiettivi ciclo 4

| # | Obiettivo | Esito atteso |
|---|-----------|--------------|
| 1 | **Toggle demo in-app** | Palestra operativa da sidebar senza redeploy / modifica `.env` |
| 2 | **Sandbox MASE integrata** | In session demo: scope `is_demo=true`, API forzate su demoapi, certificato da UI |
| 3 | **Sicurezza produzione** | Cross-write impossibile; disattivazione ripristina scope prod |
| 4 | **Conformità verticale** | Flusso demo vidima → firma → registro verificato modulo per modulo |

---

## 2. Architettura toggle sessione (Sprint 46)

```
┌──────────────────────────────────────────────────────────────────┐
│  DemoContext::isActive()                                         │
│    = APP_DEMO_MODE (deploy)  OR  session demo_mode_active        │
├──────────────────────────────────────────────────────────────────┤
│  HasDemoScope → where is_demo = isActive()                       │
│  RentriSetting::instance() → row is_demo coerente               │
│  RentriApiClient → session demo: force demoapi; stub se no cert  │
├──────────────────────────────────────────────────────────────────┤
│  Guardrail production:                                           │
│    ALLOW_SESSION_DEMO=false + APP_ENV=production → toggle 🔒     │
│    Modale conferma prima di activate()                           │
│    Middleware demo.scope → revoca session se ruolo non autorizzato│
│    Activity log su activate/deactivate                           │
└──────────────────────────────────────────────────────────────────┘
```

**Ruoli toggle:** `admin`, `segreteria` — **non** `operatore` (mobile).

**File chiave:** `DemoModeSessionService`, `DemoContext`, `DemoModeToggle` (Livewire sidebar), `DemoRentriPresetService`, `EnsureDemoModeScope`.

---

## 3. Tabella sprint 46–48 (pianificato)

| Sprint | Scope | Deliverable | Stato |
|--------|-------|-------------|-------|
| **46** | Toggle + sandbox UI | Sidebar palestra operativa, session scope, preset RENTRI sandbox, wiring API, test, docs | ✅ |
| **47** | Revisione verticale / credenziali avanzate | Isolamento anagrafiche/VFU/magazzino; policy cross-demo; cert scadenza + note operatore; walkthrough 6 step; E2E Livewire | ✅ |
| **48** | Preset multi-operatore + UX + go-live ciclo 4 | Profili operatore UI; progress bar walkthrough; scope e-commerce/MUD; checklist GO-LIVE ciclo 4 | ✅ |
| **49** | Smoke E2E + magazzino stock + CI prod | Playwright palestra; giacenza virtuale demo; workflow production.yml | ✅ |
| **50** | Chiusura ciclo 4 + go-live RENTRI | UAT formazione; GO-LIVE-RENTRI; RUNBOOK-POST-DEPLOY | ✅ |

---

## 4. Sprint 46 — dettaglio

### 4.1 UI

- Sidebar: switch «Palestra operativa» + link impostazioni RENTRI demo
- Banner: distingue session demo vs deploy demo
- Impostazioni RENTRI: preset sandbox + test connessione quando demo attivo

### 4.2 Config

| Variabile | Default | Uso |
|-----------|---------|-----|
| `ALLOW_SESSION_DEMO` | `false` | Abilita toggle su istanza condivisa (staging/local; in production solo se esplicito) |
| `RENTRI_DEMO_PRESET_*` | vuoto | Default form preset (non segreti) |

### 4.3 Test

- Toggle scope read/write
- RBAC sidebar
- API client sandbox in session demo
- Preset sandbox applicato su `RentriSetting` demo

---

## 5. Sprint 47 — completato

Revisione **verticale modulo per modulo** in palestra operativa:

1. **VFU / magazzino / anagrafiche** — `is_demo` su `anagrafiche` e `vfu_registrations`; `HasDemoScope` + `DemoTrainingScope` per filtro CER magazzino; test isolamento cross-scope.
2. **Policy** — trait `EnforcesDemoScope`; demo scope in `view`/`update`/`delete` (non in `before()`, che non riceve il modello).
3. **Credenziali demo** — badge scadenza cert in UI, campo `note_operatore`, walkthrough 6 step post-preset (cert → blocchi → trasporto → vidima/firma → registro).
4. **Test E2E Livewire** — toggle ON → seed → vidima → firma → registro con `Http::fake` MASE (`tests/Feature/Sprint47/*`).
5. **Docs** — FAQ in `PALESTRA-OPERATIVA.md`.

---

## 6. Sprint 48 — completato

1. **Preset multi-operatore sandbox** — profili `default`, `sede_nord`, `sede_sud` in `config/demo.php`; selector UI Impostazioni RENTRI.
2. **UX walkthrough** — progress bar dashboard, hint certificato in scadenza, deep link `?step=2`, link app operatore.
3. **Audit e-commerce/MUD** — `is_demo` + `HasDemoScope`; policy `EnforcesDemoScope`; audit log filtrato per `demo_mode`.
4. **GO-LIVE ciclo 4** — `docs/GO-LIVE-CICLO-4.md` checklist palestra operativa.
5. **13 test Sprint 48** in `tests/Feature/Sprint48/*`.

---

## 7. Sprint 49 — completato

1. **Playwright smoke palestra** — `tests/e2e/palestra-smoke.spec.ts`: login → toggle ON → walkthrough → preset `sede_nord`; `@playwright/test` in `package.json`.
2. **Magazzino stock demo** — `DemoTrainingScope::resolveGiacenzaKg()` da movimenti scoped; `addPeso`/`removePeso` non mutano `magazzino_rifiuti` prod in palestra.
3. **CI produzione** — `.github/workflows/production.yml`: PHPUnit + `rentri:preflight` + job Playwright (no deploy secrets).
4. **4 test Sprint 49** in `tests/Feature/Sprint49/*`.
5. Checklist `GO-LIVE-CICLO-4.md` aggiornata (parziale).

---

## 8. Sprint 50 — completato (chiusura ciclo 4)

1. **UAT formazione** — [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) runbook 90 min + checklist firmabile.
2. **GO-LIVE-RENTRI** — sezione ciclo 4 completa + link [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md).
3. **Post-deploy** — [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md): `rentri:monitor`, dead-letter, escalation.
4. **Backlog + README** — ciclo 4 marcato chiuso; test Sprint 50 docs/monitor.

---

## 9. Gap residui (post chiusura ciclo 4)

| Gap | Owner | Priorità |
|-----|-------|----------|
| UAT formazione firmato in sede | Segreteria | Alta |
| Certificati MASE produzione + `RENTRI_API_STUB=false` | IT / segreteria | Alta (go-live RENTRI) |
| Deploy produzione infra (secrets, WAF, TLS) | Infra team | Media |
| Pen-test OWASP esterno | Security | Media |
| Load test MASE / pagamenti e-commerce | Prodotto | Bassa |

---

## 10. Riferimenti

- [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) — sessione formazione guidata
- [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md) — monitor post-deploy
- [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) — guida utente
- [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md) — checklist chiusura ciclo 4
- [DEMO-DEPLOY.md](DEMO-DEPLOY.md) — deploy dedicato + toggle sessione
- [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md)

---

*Ciclo 4 — CHIUSO Sprint 50.*
