# Sprint 80 — Review handoff (agente Sprint 81)

**Destinatario:** agente Sprint 81 · **REVIEW ONLY** — verifica fix P1-3 Sprint 80, nessuna nuova feature.

**Contesto:** Ciclo 7 Enterprise — remediation P1 vidima validator dopo GO Sprint 79.

**Riferimenti:** [SPRINT-79-REVIEW-REPORT.md](SPRINT-79-REVIEW-REPORT.md) §5 · [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md)

---

## Cosa è stato fixato (Sprint 80)

| P1 | Fix | File |
|----|-----|------|
| P1-3 | `RentriFirVidimaValidator` — gate CF operatore, `num_iscr_sito`, cert mTLS (live), onboarding ≥ 3 | `app/Domain/Rentri/RentriFirVidimaValidator.php` |
| P1-3 | `assertReady()` prima submit API vidima | `app/Services/Rentri/RentriFirService.php` |
| P1-3 | Checklist pre-vidima IT + blockers operativi (blocco) | `TrasportoShow.php`, `show.blade.php` |
| — | Eccezione dedicata con errori strutturati | `RentriFirVidimaException.php` |

### Gate validator

- **CF operatore** — `cf_operatore` o fallback `cf`
- **num_iscr_sito** — obbligatorio
- **Onboarding** — `onboarding_step_completed >= 3`
- **Cert mTLS** — valido e non scaduto solo in modalità **live** (`RentriRuntimeModeService::isApiStub()` false); in stub cert sempre OK

---

## Checklist review Sprint 81 (REVIEW ONLY)

### Conformità P1-3

- [ ] Checklist OK con settings seeded (cert + onboarding 3)
- [ ] `assertReady` fallisce senza CF operatore
- [ ] `assertReady` fallisce senza `num_iscr_sito`
- [ ] `assertReady` fallisce con onboarding < 3
- [ ] Live mode + cert scaduto → blocker cert mTLS
- [ ] Stub mode → cert check saltato (OK automatico)
- [ ] `RentriFirService::vidima()` lancia `RentriFirVidimaException` **prima** di HTTP se settings KO
- [ ] UI TrasportoShow: checklist IT con badge OK/KO + messaggio correzione
- [ ] `canVidimaFir` disabilitato se checklist KO o blocco esaurito/mancante

### Regression

```bash
php artisan test --filter=Sprint80
php artisan test --filter=Sprint6
php artisan test --filter=Sprint32
php artisan test --filter=Sprint42
php artisan test --filter=Sprint78
php artisan test
```

### Non in scope Sprint 81

- P1-4 Poll xFIR timeout config dedicato → Sprint 82 fix
- P1-5 Contract test payload MASE → Sprint 82+

---

## Output atteso agente Sprint 81

1. **`docs/SPRINT-81-REVIEW-REPORT.md`** — esito ✅/⚠️/❌ per voce checklist.
2. Raccomandazione GO/NO-GO per Sprint 82 (P1-4 poll xFIR timeout).
3. No commit/push; no code changes salvo regression blocker.

---

## Istruzione ESATTA agente Sprint 82 (se GO)

Implementare **P1-4** da audit §3:

1. Timeout poll xFIR dedicato (config separato da vidima FIR).
2. Test Sprint 82 ≥5; suite verde.
3. `docs/SPRINT-82-REVIEW-HANDOFF.md`
4. No commit/push.
