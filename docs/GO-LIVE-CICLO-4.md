# GO-LIVE — Ciclo 4 (Palestra operativa)

> ## ✅ CHIUSO (codice + CI + docs — Sprint 50)
>
> Checklist **verificata automaticamente** dove indicato `[x]`.  
> Voci `[ ]` richiedono azione **operativa** in sede (UAT firmato, cert prod, deploy infra).

Checklist chiusura **Ciclo 4** — toggle sessione, sandbox MASE integrata, isolamento demo/prod.

Riferimenti: [CICLO-4-PIANO.md](CICLO-4-PIANO.md) · [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) · [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) · [RUNBOOK-POST-DEPLOY.md](RUNBOOK-POST-DEPLOY.md)

---

## 1. Ambiente e configurazione

- [x] `ALLOW_SESSION_DEMO` configurabile (`config/demo.php`, test Sprint 46)
- [ ] `ALLOW_SESSION_DEMO` impostato correttamente per **ogni** ambiente deployato
- [ ] `APP_DEMO_MODE` solo su istanza demo dedicata (non su produzione operativa)
- [x] Pipeline CI demo verificata (`.github/workflows/demo-staging.yml`)
- [ ] Certificati sandbox e produzione **fisicamente separati** in storage deploy reale

---

## 2. Isolamento dati (scope + policy)

- [x] Modelli RENTRI-critical con `HasDemoScope` (`DemoContext::scopedModels()`)
- [x] Anagrafiche, VFU, e-commerce, MUD con `is_demo` e scope globale
- [x] Policy con `EnforcesDemoScope` — nessun `before() → true` su moduli scoped
- [x] `rentri:demo-reset` elimina solo dati `is_demo=true`
- [x] Magazzino: giacenza palestra da movimenti demo (`DemoTrainingScope::resolveGiacenzaKg`)
- [x] Test isolamento Sprint 47–49 verdi
- [x] Smoke Playwright palestra (`npm run test:e2e`) verde in CI

---

## 3. Flusso palestra operativa (UAT)

- [x] Toggle ON/OFF da sidebar (segreteria/admin) con modale conferma (test Sprint 46/47)
- [x] Banner scope demo visibile in session palestra
- [x] Preset multi-operatore sandbox applicabile da Impostazioni RENTRI (Sprint 48)
- [x] Walkthrough dashboard: 6 step, progress bar, hint certificato scadenza
- [x] Percorso completo: seed → vidima → firma → registro (`PalestraOperativaE2eTest`, Playwright)
- [x] Disattivazione palestra ripristina scope prod (test isolamento)
- [ ] **UAT formazione firmato** — [UAT-FORMAZIONE-PALESTRA.md](UAT-FORMAZIONE-PALESTRA.md) §4

---

## 4. Sicurezza e RBAC

- [x] Operatore mobile **non** può attivare palestra (test Sprint 46)
- [x] Cross-write prod↔demo impossibile (`DemoIsolationException`)
- [x] Audit log filtrato in session demo (`demo_mode`)
- [x] Activity log: nessun leak prod e-commerce/MUD in palestra (Sprint 48)

---

## 5. Documentazione e formazione

- [x] `PALESTRA-OPERATIVA.md` — guida utente + FAQ
- [x] Runbook UAT formazione — `UAT-FORMAZIONE-PALESTRA.md`
- [x] Runbook post-deploy — `RUNBOOK-POST-DEPLOY.md`
- [ ] Sessione formazione segreteria **eseguita e firmata**

---

## 6. Pre-produzione RENTRI (post ciclo 4)

- [ ] Impostazioni RENTRI produzione (`is_demo=false`) con certificato reale
- [ ] `rentri:preflight` verde su produzione (0 fail, stub disabilitati)
- [x] Runbook monitor dead-letter — `RUNBOOK-POST-DEPLOY.md` + `MONITORING-CICLO-3.md`
- [x] Pipeline CI produzione — `.github/workflows/production.yml`

---

## Comandi rapidi

```bash
php artisan test --filter=Sprint47
php artisan test --filter=Sprint50
npm run test:e2e
php artisan rentri:demo-preflight
php artisan rentri:monitor
php artisan rentri:demo-seed
php artisan rentri:demo-reset
```

---

*Ciclo 4 — CHIUSO Sprint 50.*
