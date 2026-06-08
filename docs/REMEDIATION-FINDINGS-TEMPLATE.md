# Remediation findings — Template tracking

**Uso:** registrare e tracciare findings del pen-test OWASP esterno (Sprint 104+) fino a chiusura.

**UI operatore (Sprint 113):** `/admin/pen-test-prep` — registro findings, chiusura con evidenza, export markdown allineato a questo template.

**Legenda severità:**

| Livello | SLA remediation | Esempio |
|---------|-----------------|---------|
| **P0 — Critical** | 24–48 h | RCE, auth bypass totale, SQLi con dump DB |
| **P1 — High** | 7 giorni | IDOR su dati sensibili, XSS stored admin, 2FA bypass |
| **P2 — Medium** | 30 giorni | CSRF su azione non critica, info disclosure, misconfig |
| **P3 — Low / Info** | backlog | Header mancanti, verbose error staging |

**Stati:** `open` · `in_progress` · `fixed` · `accepted_risk` · `wont_fix`

---

## Registro findings

| ID | Data | Severità | OWASP | Titolo | Asset | Stato | Owner | Target fix | Verifica |
|----|------|----------|-------|--------|-------|-------|-------|------------|----------|
| PT-001 | | P0/P1/P2/P3 | A0X | | | open | | | |
| PT-002 | | | | | | | | | |
| PT-003 | | | | | | | | | |

---

## Template dettaglio finding

Copiare una sezione per ogni finding:

### PT-XXX — [Titolo breve]

| Campo | Valore |
|-------|--------|
| **Severità** | P0 / P1 / P2 / P3 |
| **OWASP category** | A01 Broken Access Control / … |
| **Asset** | `/segreteria/...` |
| **Reporter** | Vendor / Internal |
| **Stato** | open |

**Descrizione:**

> Cosa è vulnerabile e perché.

**Proof of concept:**

```
Passi per riprodurre (curl, screenshot ref, request Burp).
```

**Impatto:**

> Dati esposti, privilege escalation, compliance.

**Remediation proposta:**

> Fix tecnico (policy, middleware, validazione, WAF rule).

**Fix implementato:**

> PR/commit reference, data deploy.

**Verifica re-test:**

- [ ] Vendor conferma chiusura
- [ ] Test regressione PHPUnit aggiunto
- [ ] OWASP checklist interna aggiornata

---

## Riepilogo per sprint

| Sprint | P0 open | P1 open | P2 open | P3 open | Chiusi |
|--------|---------|---------|---------|---------|--------|
| 104 (prep) | — | — | — | — | — |
| Post vendor report | | | | | |

---

## Gate go-live

- [ ] **Zero P0 aperte** prima produzione
- [ ] **P1** — piano remediation approvato o accettazione rischio firmata
- [ ] WAF attivo (Sprint 105) con regole allineate a findings
- [ ] Re-test vendor opzionale su findings P0/P1

---

## Riferimenti

- [PEN-TEST-EXTERNAL-SCOPE.md](PEN-TEST-EXTERNAL-SCOPE.md)
- [OWASP-INTERNAL-CHECKLIST.md](OWASP-INTERNAL-CHECKLIST.md)
- [GO-LIVE-360.md](GO-LIVE-360.md) § security sign-off

---

*Template Sprint 104 — popolare dopo report vendor.*
