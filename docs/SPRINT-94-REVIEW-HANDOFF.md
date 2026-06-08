# Sprint 94 — Review handoff (agente Sprint 95)

**Destinatario:** agente Sprint 95 · **MUD invio telematico live prep** (non stub protocol).

**Contesto:** Ciclo 8 — payload vidima OpenAPI alignment completato.

**Riferimenti:** [SPRINT-94-AUDIT-NOTES.md](SPRINT-94-AUDIT-NOTES.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 94)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Mapper strip CRM vidima | `RentriFirVidimaTransmissionMapper.php` |
| 2 | Request body MASE-only | `RentriFirVidimaRequest.php` |
| 3 | CRM audit in transazioni | `RentriApiClient.php` (`crm_audit`) |
| 4 | Fixture aggiornata | `tests/fixtures/rentri/mase/vidima-submit.json` |
| 5 | Test Sprint 94 | `tests/Feature/Sprint94/RentriVidimaOpenApiAlignmentTest.php` (7 test) |
| 6 | Audit doc | `docs/SPRINT-94-AUDIT-NOTES.md` |

### Chiavi body MASE

`num_iscr_sito`, `progressivo` (opzionale)

### Escluse dalla trasmissione

`trasporto_id`, `codice_blocco` (path URL)

---

## Checklist review Sprint 95

### Regression

```bash
php artisan test --filter=Sprint94
php artisan test --filter=Sprint84
php artisan test --filter=Sprint32
php artisan test --filter=Sprint88
php artisan test
```

---

## Istruzione ESATTA agente Sprint 95

**MUD invio telematico live prep:**

1. Audit `MudTelematicoService` / invio stub `MUD-STUB-*` vs requisiti ministeriali.
2. **`MudTelematicoTransmissionService`** — adapter submit async con protocollo reale (sandbox prep).
3. Config `MUD_TELEMATICO_*` in `config/services.php` + `.env.example`.
4. UI MUD — badge stub/live + checklist pre-invio.
5. Test Sprint 95 ≥6.
6. `docs/SPRINT-95-REVIEW-HANDOFF.md` + `CICLO-8-PIANO.md`
7. No commit/push salvo richiesta utente

**Gap ciclo 6:** MUD telematico live — attualmente solo stub protocollo.

---

## Output atteso agente Sprint 95

1. Prep invio MASE documentata + adapter stub configurabile verso live.
2. Regression suite verde (599 test attesi, 4 skipped).
3. Handoff Sprint 96 (gateway pagamento e-commerce).
