# Sprint 95 — Review handoff (agente Sprint 96)

**Destinatario:** agente Sprint 96 · **Gateway pagamento e-commerce (Stripe stub configurabile)**.

**Contesto:** Ciclo 8 — MUD invio telematico live prep completato.

**Riferimenti:** [SPRINT-95-AUDIT-NOTES.md](SPRINT-95-AUDIT-NOTES.md) · [CICLO-8-PIANO.md](CICLO-8-PIANO.md)

---

## Cosa è stato implementato (Sprint 95)

| # | Deliverable | File |
|---|-------------|------|
| 1 | Runtime mode stub/live | `MudTelematicoRuntimeModeService.php` |
| 2 | Transmission async stub/live | `MudTelematicoTransmissionService.php` |
| 3 | Mapper body MASE + CRM audit | `MudTelematicoTransmissionMapper.php` |
| 4 | Invio orchestration refactor | `MudInvioTelematicoService.php` |
| 5 | Config env | `config/services.php`, `.env.example` |
| 6 | UI badge + checklist live | `MudShow.php`, `mud/show.blade.php`, `mud-telematico-mode-badge` |
| 7 | Fixture contratto | `tests/fixtures/mud/mase-invio-submit.json` |
| 8 | Test Sprint 95 | `tests/Feature/Sprint95/MudTelematicoLivePrepTest.php` (9 test) |
| 9 | Audit doc | `docs/SPRINT-95-AUDIT-NOTES.md` |

### Env chiave

- `MUD_TELEMATICO_STUB` (default `true`)
- `MUD_TELEMATICO_BASE_URL` (placeholder sandbox)

### Body MASE submit

`anno_riferimento`, `xml`, `xml_encoding`, `schema_version`

### Esclusi dal wire

`dichiarazione_id`, `totali` → `crm_audit` in `invio_risposta`

---

## Checklist review Sprint 96

```bash
php artisan test --filter=Sprint95
php artisan test --filter=Sprint65
php artisan test
```

---

## Istruzione ESATTA agente Sprint 96

**Gateway pagamento e-commerce (Stripe stub configurabile):**

1. Audit checkout e-commerce attuale (token stub) vs integrazione Stripe sandbox.
2. **`EcommercePaymentGatewayService`** — adapter stub/live (pattern `MudTelematicoTransmissionService` / `RentriRuntimeModeService`).
3. Config `ECOMMERCE_PAYMENT_STUB`, `STRIPE_*` in `config/services.php` + `.env.example`.
4. UI checkout — badge stub/live + preflight (chiavi API, webhook secret).
5. Test Sprint 96 ≥6.
6. `docs/SPRINT-96-REVIEW-HANDOFF.md` + `CICLO-8-PIANO.md`
7. No commit/push salvo richiesta utente

**Gap ciclo 6:** Gateway pagamento reale — checkout attualmente stub token.

---

## Output atteso agente Sprint 96

1. Stripe sandbox prep documentata + adapter configurabile.
2. Regression suite verde (608+ test attesi, 4 skipped).
3. Handoff Sprint 97 (2FA enforced admin/segreteria).
