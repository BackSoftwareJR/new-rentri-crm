# Sprint 91 — Review handoff (agente Sprint 92)

**Destinatario:** agente Sprint 92 · **CI gated integration test sandbox** (o REVIEW ONLY se preferito dal parent).

**Contesto:** Ciclo 8 — validazione operativa reale RENTRI; primo sprint implementato.

**Riferimenti:** [VALIDAZIONE-SANDBOX-MASE.md](VALIDAZIONE-SANDBOX-MASE.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 91)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Orchestrazione validazione sandbox | `RentriSandboxValidationService.php` |
| 2 | UI wizard «Test reale MASE» | `RentriSettings.php`, `rentri-settings.blade.php` |
| 3 | Integration test hardened (dual env gate) | `RentriIntegrationTest.php`, `SeedsRentriCertificate.php` |
| 4 | Guida operatore | `docs/VALIDAZIONE-SANDBOX-MASE.md` |
| 5 | Preflight cert path opzionale | `PreflightService.php` |
| 6 | Config env | `config/services.php`, `.env.example` |
| 7 | Test Sprint 91 | `tests/Feature/Sprint91/RentriSandboxValidationTest.php` (8 test) |

### Env vars nuove

- `RENTRI_SANDBOX_CERT_PATH` — percorso PKCS#12 fuori repo
- `RENTRI_SANDBOX_CERT_PASSWORD` — password keystore

### Comportamento stub default

- `RENTRI_API_STUB=true` (default) invariato
- Validazione UI mostra prerequisiti KO su modalità stub — nessuna regressione stub offline

---

## Checklist review Sprint 92

### Conformità Sprint 91

- [ ] `RentriSandboxValidationService` — prerequisiti + health + codifiche + vidima info
- [ ] UI sezione visibile post-onboarding con link demoapi
- [ ] `RentriIntegrationTest` skip senza `RENTRI_INTEGRATION_TEST` + `RENTRI_SANDBOX_CERT_PATH`
- [ ] Nessun cert reale in repo
- [ ] Preflight opzionale su sandbox cert path
- [ ] Doc VALIDAZIONE-SANDBOX-MASE completa

### Regression

```bash
php artisan test --filter=Sprint91
php artisan test --filter=Sprint31
php artisan test --filter=Sprint76
php artisan test --filter=Sprint90
php artisan test
```

---

## Istruzione ESATTA agente Sprint 92

**Implementazione CI gated integration test sandbox:**

1. Workflow GitHub Actions (o estensione `production.yml`) con job **opzionale/manual** `rentri-sandbox-integration`.
2. Secret: `RENTRI_SANDBOX_CERT_BASE64`, `RENTRI_SANDBOX_CERT_PASSWORD` — decode in CI, mai loggare.
3. Gate: job esegue `RentriIntegrationTest` solo se secret presenti; altrimenti skip esplicito.
4. Documentare in `VALIDAZIONE-SANDBOX-MASE.md` §CI e `CICLO-8-PIANO.md`.
5. Test Sprint 92 ≥5 (workflow presence, skip without secrets, doc links).
6. `docs/SPRINT-92-REVIEW-HANDOFF.md`
7. No commit/push salvo richiesta utente.

**Vincoli:** no traffic MASE in CI default branch; job manual o label `integration-sandbox`.

---

## Output atteso agente Sprint 92

1. CI gated funzionante (skip senza secret).
2. Regression suite verde.
3. Handoff Sprint 93 (SLA dashboard).
