# Sprint 82 — Review handoff (agente Sprint 83)

**Destinatario:** agente Sprint 83 · **REVIEW ONLY** — verifica fix P1-4 Sprint 82, nessuna nuova feature.

**Contesto:** Ciclo 7 Enterprise — config poll xFIR dedicato dopo GO Sprint 81.

**Riferimenti:** [SPRINT-81-REVIEW-REPORT.md](SPRINT-81-REVIEW-REPORT.md) §5 · [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md)

---

## Cosa è stato fixato (Sprint 82)

| P1 | Fix | File |
|----|-----|------|
| P1-4 | Config dedicato `xfir_poll_max_attempts` / `xfir_poll_interval_ms` | `config/services.php`, `.env.example` |
| P1-4 | `waitXfirTrasmissioneResult()` usa config xFIR, non `fir_poll_*` | `RentriApiClient.php` |
| P1-4 | Message mapper timeout xFIR con valori config dedicati | `RentriXfirTransmissionMessageMapper.php` |
| P1-4 | Integrazione mapper in transmission service | `RentriXfirTransmissionService.php` |

### Default config (separati da vidima)

| Chiave | Default | Env |
|--------|---------|-----|
| `xfir_poll_max_attempts` | 20 | `RENTRI_XFIR_POLL_MAX_ATTEMPTS` |
| `xfir_poll_interval_ms` | 300 | `RENTRI_XFIR_POLL_INTERVAL_MS` |
| `fir_poll_max_attempts` | 15 | (invariato) |
| `registro_poll_*` | 15 / 200 | (invariato) |

---

## Checklist review Sprint 83 (REVIEW ONLY)

### Conformità P1-4

- [ ] Config xFIR presente e distinto da `fir_poll_*`
- [ ] `.env.example` documenta `RENTRI_XFIR_POLL_*`
- [ ] `waitXfirTrasmissioneResult()` legge `xfir_poll_*` (non `fir_poll_*`)
- [ ] Timeout xFIR → messaggio IT con tentativi/secondi da config xFIR
- [ ] `RentriXfirTransmissionService` usa mapper per eccezioni API
- [ ] Vidima FIR invariata — continua a usare `fir_poll_*`

### Regression

```bash
php artisan test --filter=Sprint82
php artisan test --filter=Sprint39
php artisan test --filter=Sprint42
php artisan test --filter=Sprint80
php artisan test
```

### Non in scope Sprint 83

- P1-5 Contract test payload vidima/registro vs fixture MASE → Sprint 84+
- UI copy stub/live sweep → Sprint 85+

---

## Output atteso agente Sprint 83

1. **`docs/SPRINT-83-REVIEW-REPORT.md`** — esito ✅/⚠️/❌ per voce checklist.
2. Raccomandazione GO/NO-GO per Sprint 84 (P1-5 contract test payload MASE).
3. No commit/push; no code changes salvo regression blocker.

---

## Istruzione ESATTA agente Sprint 84 (se GO)

Implementare **P1-5** da audit §3:

1. Contract test payload vidima/xFIR/registro vs fixture OpenAPI MASE.
2. Test Sprint 84 ≥5; suite verde.
3. `docs/SPRINT-84-REVIEW-HANDOFF.md`
4. No commit/push.
