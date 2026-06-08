# Sprint 88 — Review handoff (agente Sprint 89)

**Destinatario:** agente Sprint 89 · **REVIEW ONLY** — verifica audit/fix payload_firmato Sprint 88, nessuna nuova feature.

**Contesto:** Ciclo 7 Enterprise — shape COSE xFIR vs MASE dopo GO Sprint 87.

**Riferimenti:** [SPRINT-88-AUDIT-NOTES.md](SPRINT-88-AUDIT-NOTES.md) · [SPRINT-87-REVIEW-REPORT.md](SPRINT-87-REVIEW-REPORT.md)

---

## Cosa è stato fixato (Sprint 88)

| ID | Fix | File |
|----|-----|------|
| M-88-1 | Mapper strip metadati CRM da `payload_firmato` | `RentriXfirCoseTransmissionMapper.php` |
| M-88-1 | Request usa mapper in `body()` | `RentriXfirTrasmissioneRequest.php` |
| M-88-2 | Fixture COSE completa | `tests/fixtures/rentri/mase/xfir-cose-sign1.json` |
| — | Esempio xfir-trasmissione allineato | `xfir-trasmissione.json` |
| — | Audit documentato | `docs/SPRINT-88-AUDIT-NOTES.md` |

### Chiavi COSE MASE (payload_firmato)

`typ`, `alg`, `protected`, `payload`, `signature`

### Esclusi dalla trasmissione

`api_mode`, `numero_fir`, `firmato_at`, `stub`

---

## Checklist review Sprint 89 (REVIEW ONLY)

### Conformità audit

- [ ] Fixture `xfir-cose-sign1.json` con required + crm_excluded_keys
- [ ] Signer stub produce 5 campi COSE
- [ ] Protected header decodifica COSE_Sign1 + alg
- [ ] `RentriXfirTrasmissioneRequest` non invia metadati CRM
- [ ] Root `typ` allineato a `payload_firmato.typ`
- [ ] `xfir_signed_payload` locale conserva metadati CRM (download/audit)
- [ ] Signer COSE invariato (fix solo mapper/request)

### Regression

```bash
php artisan test --filter=Sprint88
php artisan test --filter=Sprint84
php artisan test --filter=Sprint39
php artisan test --filter=Sprint34
php artisan test
```

### Non in scope Sprint 89

- Monitoring SLA RENTRI → Sprint 89+ (ops)
- Chiusura ciclo 7 → Sprint 90

---

## Output atteso agente Sprint 89

1. **`docs/SPRINT-89-REVIEW-REPORT.md`** — esito ✅/⚠️/❌ per voce checklist.
2. Raccomandazione GO/NO-GO per Sprint 90 (chiusura ciclo 7).
3. No commit/push; no code changes salvo regression blocker.

---

## Istruzione ESATTA agente Sprint 90 (se GO)

Chiusura **Ciclo 7 Enterprise**:

1. `docs/GO-LIVE-ENTERPRISE.md` — checklist GO-LIVE RENTRI/FIR post-remediation P0–P2.
2. Aggiornare `CICLO-7-ENTERPRISE-AUDIT.md` matrice conformità finale.
3. Test smoke regression suite completa verde.
4. `docs/SPRINT-90-REVIEW-HANDOFF.md`
5. No commit/push.
