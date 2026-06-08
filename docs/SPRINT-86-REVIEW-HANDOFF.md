# Sprint 86 — Review handoff (agente Sprint 87)

**Destinatario:** agente Sprint 87 · **REVIEW ONLY** — verifica fix P2-1 Sprint 86, nessuna nuova feature.

**Contesto:** Ciclo 7 Enterprise — UI copy stub/live sweep dopo GO Sprint 85.

**Riferimenti:** [SPRINT-85-REVIEW-REPORT.md](SPRINT-85-REVIEW-REPORT.md) §5 · [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md)

---

## Cosa è stato fixato (Sprint 86)

| P2 | Fix | File |
|----|-----|------|
| P2-1 | `apiModeKind()` + label IT stub/live/offline + variant badge | `RentriRuntimeModeService.php` |
| P2-1 | Component riusabile | `resources/views/components/rentri-api-mode-badge.blade.php` |
| P2-1 | Badge su TrasportoShow (header + tracking) | `show.blade.php`, `TrasportoShow.php` |
| P2-1 | Badge hub RENTRI registro | `rentri.blade.php`, `Rentri.php` |
| P2-1 | Badge elenco FIR | `fir/index.blade.php`, `FirIndex.php` |
| P2-1 | Badge + KPI Impostazioni RENTRI | `rentri-settings.blade.php`, `RentriSettings.php` |
| P2-1 | Messaggi flash vidima/firma/registro via runtime mapper | `TrasportoShow.php`, `Rentri.php` |

### Label runtime

| Kind | Label | Variant |
|------|-------|---------|
| offline | demo offline | warning |
| stub | stub sandbox | info |
| live | RENTRI live | success |

---

## Checklist review Sprint 87 (REVIEW ONLY)

### Conformità P2-1

- [ ] `apiModeDisplayLabel()` restituisce stub/live/offline corretti
- [ ] Component `x-rentri-api-mode-badge` visibile su TrasportoShow, RENTRI hub, FirIndex, RentriSettings
- [ ] Tracking section TrasportoShow mostra badge accanto a titolo GPS
- [ ] Copy hardcoded «invio via API stub» sostituita con label runtime
- [ ] Messaggi success vidima/firma/registro usano `apiModeDisplayLabelFromApiMode()`
- [ ] Nessun refactor service layer RENTRI (solo UI + runtime labels)

### Regression

```bash
php artisan test --filter=Sprint86
php artisan test --filter=Sprint76
php artisan test --filter=Sprint70
php artisan test --filter=Sprint84
php artisan test
```

### Non in scope Sprint 87

- xFIR `payload_firmato` shape audit → Sprint 88+
- Monitoring SLA RENTRI → Sprint 89+

---

## Output atteso agente Sprint 87

1. **`docs/SPRINT-87-REVIEW-REPORT.md`** — esito ✅/⚠️/❌ per voce checklist.
2. Raccomandazione GO/NO-GO per Sprint 88 (xFIR payload shape audit).
3. No commit/push; no code changes salvo regression blocker.

---

## Istruzione ESATTA agente Sprint 88 (se GO)

Implementare **xFIR payload_firmato shape audit** da audit §3:

1. Audit diff shape `payload_firmato` vs OpenAPI/COSE MASE; fix minimi se mismatch.
2. Test Sprint 88 ≥5; suite verde.
3. `docs/SPRINT-88-REVIEW-HANDOFF.md`
4. No commit/push.
