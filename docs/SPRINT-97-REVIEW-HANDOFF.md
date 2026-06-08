# Sprint 97 — Review handoff (agente Sprint 98)

**Destinatario:** agente Sprint 98 · **Tracking GPS trasporti provider prep**.

**Riferimenti:** [2FA-PREP-RUNBOOK.md](2FA-PREP-RUNBOOK.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 97)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Config enforcement | `config/two-factor.php`, `.env.example` |
| 2 | Enforcement service | `TwoFactorEnforcementService.php` |
| 3 | Middleware | `EnsureTwoFactorEnabled.php` |
| 4 | UI banner + redirect IT | `two-factor-enforcement-banner`, `security-settings` |
| 5 | Route middleware | `segreteria.*`, `admin.*` groups |
| 6 | Test Sprint 97 | `tests/Feature/Sprint97/TwoFactorEnforcementTest.php` (10 test) |

### Env chiave

- `TWO_FACTOR_ENFORCE_ADMIN_SEGRETERIA` (default `false`)
- `TWO_FACTOR_ENFORCE_GRACE_UNTIL` (ISO datetime opzionale)

### Ruoli

| Ruolo | Enforcement |
|-------|-------------|
| admin | ✅ se config attiva |
| segreteria | ✅ se config attiva |
| editor | ❌ escluso |
| operatore | ❌ escluso |

---

## Istruzione ESATTA agente Sprint 98

**Tracking GPS trasporti provider prep:**

1. Audit tracking trasporti attuale (stub/manual) vs provider GPS reale.
2. **`TrasportoGpsTrackingService`** — adapter stub/live configurabile.
3. Config `TRASPORTO_GPS_*` in `config/services.php` + `.env.example`.
4. UI TrasportoShow — badge stub/live + posizione ultima rilevata.
5. Test Sprint 98 ≥6.
6. `docs/SPRINT-98-REVIEW-HANDOFF.md` + `CICLO-8-PIANO.md`
7. No commit/push salvo richiesta utente

---

## Output atteso agente Sprint 98

1. GPS provider prep documentata + adapter configurabile.
2. Regression suite verde (632+ test attesi, 4 skipped).
3. Handoff Sprint 99 (SMTP notifiche live).
