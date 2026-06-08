# Sprint 84 — Review handoff (agente Sprint 85)

**Destinatario:** agente Sprint 85 · **REVIEW ONLY** — verifica fix P1-5 Sprint 84, nessuna nuova feature.

**Contesto:** Ciclo 7 Enterprise — contract test payload MASE dopo GO Sprint 83.

**Riferimenti:** [SPRINT-83-REVIEW-REPORT.md](SPRINT-83-REVIEW-REPORT.md) §5 · [CICLO-7-ENTERPRISE-AUDIT.md](CICLO-7-ENTERPRISE-AUDIT.md)

---

## Cosa è stato fixato (Sprint 84)

| P1 | Fix | File |
|----|-----|------|
| P1-5 | Fixture OpenAPI MASE vidima / xFIR / registro | `tests/fixtures/rentri/mase/*.json` |
| P1-5 | Contract test campi obbligatori, tipi, enum CARICO/SCARICO | `tests/Feature/Sprint84/RentriMasePayloadContractTest.php` |
| P1-5 | Trait caricamento fixture | `tests/Support/LoadsMaseFixtures.php` |

### Fixture ministeriali

| Fixture | Endpoint MASE | DTO verificato |
|---------|---------------|----------------|
| `vidima-submit.json` | `POST /vidimazione-formulari/v1.0/{codice_blocco}` | `RentriFirVidimaRequest::body()` |
| `xfir-trasmissione.json` | `POST .../xfir/trasmissione` | `RentriXfirTrasmissioneRequest::body()` |
| `registro-trasmissione.json` | `POST /registro/v1.0/trasmissione` | `RentriRegistroTrasmissioneRequest::body()` |

### Enum registro

- `carico` → `CARICO`
- `scarico` → `SCARICO`

---

## Checklist review Sprint 85 (REVIEW ONLY)

### Conformità P1-5

- [ ] Fixture MASE presenti (3 file) con `required`, `properties`, `example`
- [ ] Contract vidima: `num_iscr_sito` obbligatorio + tipi integer/string
- [ ] Contract xFIR: campi ministeriali + `typ` enum `COSE_Sign1`
- [ ] Contract registro: root + movimenti con `tipo_movimento` CARICO/SCARICO
- [ ] `quantita_kg` float, `data_movimento` formato `Y-m-d`
- [ ] Nessuna modifica production payload (test+fixture only)

### Regression

```bash
php artisan test --filter=Sprint84
php artisan test --filter=Sprint33
php artisan test --filter=Sprint39
php artisan test --filter=Sprint82
php artisan test
```

### Non in scope Sprint 85

- UI copy stub/live sweep → Sprint 86+
- xFIR `payload_firmato` shape audit → Sprint 87+

---

## Output atteso agente Sprint 85

1. **`docs/SPRINT-85-REVIEW-REPORT.md`** — esito ✅/⚠️/❌ per voce checklist.
2. Raccomandazione GO/NO-GO per Sprint 86 (UI copy stub/live sweep).
3. No commit/push; no code changes salvo regression blocker.

---

## Istruzione ESATTA agente Sprint 86 (se GO)

Implementare **P2-1 / UI polish** da audit:

1. Copy tracking «stub» sempre visibile dove mancante (TrasportoShow, RENTRI hub).
2. Test Sprint 86 ≥5; suite verde.
3. `docs/SPRINT-86-REVIEW-HANDOFF.md`
4. No commit/push.
