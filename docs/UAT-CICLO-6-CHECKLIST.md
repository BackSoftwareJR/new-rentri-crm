# UAT Ciclo 6 — Completamento verticale moduli CRM RENTRI

**Ciclo 6 · Sprint 75** · Checklist accettazione utente per moduli completati (sprint 61–74).

**Ruoli tester:** segreteria, operatore bonifica, admin.

**Ambiente consigliato:** staging con palestra operativa attivabile; certificati sandbox MASE; Redis opzionale (KPI cache fallback array).

**Baseline UX:** percorsi ciclo 5 in [UAT-UX-360-CHECKLIST.md](UAT-UX-360-CHECKLIST.md) — questa checklist copre **solo i delta sprint 61–74**.

---

## Legenda esito

| Simbolo | Significato |
|---------|-------------|
| ☐ | Da verificare |
| ✅ | OK |
| ⚠️ | OK con riserva (annotare) |
| ❌ | Bloccante |

---

## Sprint 61 — E-commerce completo

**Route:** `/segreteria/ecommerce`, `/operatore/ricambi`

| # | Verifica | ☐ |
|---|----------|---|
| 61.1 | Upload immagine prodotto (JPG/PNG/WebP, max 2 MB) — thumb in catalogo | |
| 61.2 | Checkout ordine: bozza → pagamento_in_attesa → confermato / annullato | |
| 61.3 | Token checkout visibile su dettaglio ordine | |
| 61.4 | Elenco ordini recenti in dashboard e-commerce | |
| 61.5 | Demo scope: operatore non modifica ordini prod | |

---

## Sprint 62 — Anagrafiche avanzate

**Route:** `/segreteria/anagrafiche`

| # | Verifica | ☐ |
|---|----------|---|
| 62.1 | Validazione P.IVA/CF con messaggi IT su form | |
| 62.2 | Dashboard alert autorizzazioni in scadenza (widget + link) | |
| 62.3 | CRUD anagrafica trasportatore/produttore base | |

---

## Sprint 63 — VFU avanzato

**Route:** `/segreteria/vfu`, `/operatore/bonifica`

| # | Verifica | ☐ |
|---|----------|---|
| 63.1 | Upload allegato documento pratica VFU | |
| 63.2 | Export CSV storico stati pratica | |
| 63.3 | Timeline stati + certificato rottamazione (regressione ciclo 5) | |

---

## Sprint 64 — Magazzino & report

**Route:** `/segreteria/magazzino`, registro movimenti

| # | Verifica | ☐ |
|---|----------|---|
| 64.1 | Export registro movimenti CSV | |
| 64.2 | Alert soglie serbatoio in dashboard (badge + elenco) | |
| 64.3 | Email stub serbatoio (log driver) — verifica activity/notifica | |

---

## Sprint 65 — MUD telematico prep

**Route:** `/segreteria/mud`

| # | Verifica | ☐ |
|---|----------|---|
| 65.1 | Checklist pre-invio MUD (completata, payload, righe CER, XML) | |
| 65.2 | Validazione XML stub + export JSON/PDF | |
| 65.3 | Invio telematico stub — protocollo `MUD-STUB-*` + activity log | |

---

## Sprint 66 — Notifiche centralizzate

**Route:** `/segreteria/impostazioni/notifiche`

| # | Verifica | ☐ |
|---|----------|---|
| 66.1 | Toggle preferenze evento (bonifica, serbatoio, MUD, RENTRI dead-letter) | |
| 66.2 | Notifica bonifica/serbatoio su trigger reale (log mail) | |
| 66.3 | Coda opzionale `SendNotificationJob` — nessun errore Horizon | |

---

## Sprint 67 — 2FA TOTP (slice)

**Route:** `/segreteria/impostazioni/sicurezza`

| # | Verifica | ☐ |
|---|----------|---|
| 67.1 | Setup QR TOTP + conferma codice | |
| 67.2 | Login challenge opt-in post-password (utente con 2FA attivo) | |
| 67.3 | Disable 2FA — ripristino login standard | |

---

## Sprint 68 — Report & analytics

**Route:** `/segreteria` (dashboard)

| # | Verifica | ☐ |
|---|----------|---|
| 68.1 | Filtro periodo analytics (mese corrente, precedente, 3/6 mesi) | |
| 68.2 | Widget confronto periodo + trend 6 mesi | |
| 68.3 | Export CSV KPI mensile (`exportKpiCsv`) | |

---

## Sprint 69 — RENTRI prod hardening

**Route:** `/segreteria/impostazioni/rentri`, `/segreteria/rentri`

| # | Verifica | ☐ |
|---|----------|---|
| 69.1 | Checklist prod readiness 6 voci in step «Passaggio produzione» | |
| 69.2 | Switch live guidato con conferma + activity log | |
| 69.3 | Banner prod/stub su dashboard e RENTRI se mismatch | |

---

## Sprint 70 — Trasporti & FIR polish

**Route:** trasporti, `/segreteria/fir`

| # | Verifica | ☐ |
|---|----------|---|
| 70.1 | Export CSV bulk FIR (filtri periodo/stato) | |
| 70.2 | FirIndex — filtri data, badge tracking stub | |
| 70.3 | TrasportoShow — timeline tracking prep + ETA stub | |

---

## Sprint 71 — Bonifica operatore

**Route:** `/operatore/ricambi`, `/operatore/bonifica`

| # | Verifica | ☐ |
|---|----------|---|
| 71.1 | Foto operatore collegate a prodotto catalogo e-commerce | |
| 71.2 | Upload bulk foto ricambi (max 10) | |
| 71.3 | Checklist fase pericolosi 4 step — blocco complete se incompleta | |

---

## Sprint 72 — Legacy import advanced

**Route:** `/segreteria` (widget sync), CLI

| # | Verifica | ☐ |
|---|----------|---|
| 72.1 | Widget dashboard — ultimo sync, diff nuovi/aggiornati/skipped | |
| 72.2 | Command `legacy:sync-incremental` — run log persistito | |
| 72.3 | Gate `legacy.sync` / `legacy.viewRuns` — operatore negato | |

---

## Sprint 73 — Audit export live

**Route:** `/admin/audit`

| # | Verifica | ☐ |
|---|----------|---|
| 73.1 | Storico export live con checksum SHA-256 | |
| 73.2 | Download admin (signed route local o presigned S3) | |
| 73.3 | Command `audit:export-scheduled` — run registrato | |

---

## Sprint 74 — Performance & load

**Route:** `/segreteria`, CLI/k6

| # | Verifica | ☐ |
|---|----------|---|
| 74.1 | Badge KPI cache hit/miss in dashboard | |
| 74.2 | Pulsante «Refresh KPI» invalida cache (badge miss post-click) | |
| 74.3 | k6 autenticato — `k6 run scripts/k6-authenticated.js` smoke verde | |

---

## Regressione cross-modulo

| # | Verifica | ☐ |
|---|----------|---|
| R.1 | Palestra operativa — toggle demo, scope `is_demo` (ciclo 4) | |
| R.2 | Isolamento demo e-commerce/MUD/VFU (ciclo 5) | |
| R.3 | Preflight `php artisan rentri:preflight --demo` verde | |
| R.4 | Suite PHPUnit completa verde (508+) | |

---

## Sign-off UAT ciclo 6

| Ruolo | Nome | Data | Firma |
|-------|------|------|-------|
| Segreteria / operazioni | | | |
| Operatore bonifica | | | |
| Tech lead | | | |

**Esito ciclo 6 moduli:** ☐ Accettato · ☐ Accettato con riserve · ☐ Rinviato

**Riserve documentate:**

---

## Riferimenti

| Documento | Contenuto |
|-----------|-----------|
| [CICLO-6-PIANO-MODULI-COMPLETI.md](CICLO-6-PIANO-MODULI-COMPLETI.md) | Piano sprint 61–75 |
| [GO-LIVE-CICLO-6.md](GO-LIVE-CICLO-6.md) | Sign-off e smoke commands |
| [GO-LIVE-360.md](GO-LIVE-360.md) | Baseline ciclo 5 |
| [PERFORMANCE-MONITORING.md](PERFORMANCE-MONITORING.md) | KPI cache, k6, Horizon |
