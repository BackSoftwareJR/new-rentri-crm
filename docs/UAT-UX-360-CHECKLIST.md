# UAT UX 360° — CRM RENTRI autodemolitore

**Ciclo 5 · Sprint 60** · Checklist accettazione utente per go-live UX.

**Ruoli tester:** segreteria, operatore bonifica, admin (facoltativo: editor).

**Ambiente consigliato:** staging con palestra operativa attivabile; certificati sandbox MASE caricati.

---

## Legenda esito

| Simbolo | Significato |
|---------|-------------|
| ☐ | Da verificare |
| ✅ | OK |
| ⚠️ | OK con riserva (annotare) |
| ❌ | Bloccante |

---

## 1. Segreteria — percorsi core

### 1.1 Dashboard (`/segreteria`)

| # | Verifica | ☐ |
|---|----------|---|
| 1.1.1 | KPI VFU, magazzino, RENTRI visibili e cliccabili | |
| 1.1.2 | Azioni rapide portano alle pagine corrette | |
| 1.1.3 | Widget riordinabili (drag) e ordine persistito al refresh | |
| 1.1.4 | Tour onboarding first-run (Salta / Avanti / Fine) | |
| 1.1.5 | Toggle tema scuro / alto contrasto funzionanti | |
| 1.1.6 | Sezione migrazione legacy con badge Importato/Vuoto | |

### 1.2 VFU (`/segreteria/vfu`)

| # | Verifica | ☐ |
|---|----------|---|
| 1.2.1 | Elenco pratiche con filtri e empty state | |
| 1.2.2 | Wizard nuova accettazione — label/errori in italiano | |
| 1.2.3 | Dettaglio pratica — timeline stati visuale | |
| 1.2.4 | Anteprima certificato rottamazione + Stampa | |
| 1.2.5 | Help contextual (?) apre tooltip e dialog | |

### 1.3 Magazzino & registro

| # | Verifica | ☐ |
|---|----------|---|
| 1.3.1 | Giacenze per CER, alert soglia | |
| 1.3.2 | Registro movimenti — filtri, paginazione 25/pag | |
| 1.3.3 | Stampa registro — layout stampa leggibile (solo tabella) | |
| 1.3.4 | Empty state registro senza movimenti | |

### 1.4 Trasporti & FIR

| # | Verifica | ☐ |
|---|----------|---|
| 1.4.1 | Creazione/avanzamento trasporto (preparazione → transito → completato) | |
| 1.4.2 | Vidima FIR + throttle (messaggio se troppo frequente) | |
| 1.4.3 | Firma xFIR + invio MASE (stub o sandbox) | |
| 1.4.4 | Tracking GPS stub visibile solo in transito | |
| 1.4.5 | Flash success/error annunciati (screen reader / aria-live) | |

### 1.5 Amministrazione

| # | Verifica | ☐ |
|---|----------|---|
| 1.5.1 | MUD — bozza, completamento, export JSON/PDF stub | |
| 1.5.2 | E-commerce — catalogo, carrello, ordine bozza | |
| 1.5.3 | Anagrafiche e codici CER — CRUD base | |

---

## 2. RENTRI — percorsi ministeriali

### 2.1 Impostazioni (`/segreteria/impostazioni/rentri`)

| # | Verifica | ☐ |
|---|----------|---|
| 2.1.1 | Wizard step: operatore → cert mTLS → cert firma → test connessione | |
| 2.1.2 | Preview scadenza certificati | |
| 2.1.3 | Upload solo `.p12`/`.pfx` — rifiuto altri formati | |
| 2.1.4 | Preset sandbox applicabile | |

### 2.2 Trasmissione registro (`/segreteria/rentri`)

| # | Verifica | ☐ |
|---|----------|---|
| 2.2.1 | Selezione periodo + anteprima movimenti | |
| 2.2.2 | Checklist conformità — pulsante disabilitato se KO | |
| 2.2.3 | Trasmissione con rate limit (3/min) | |
| 2.2.4 | Storico API / transazioni / retry dead-letter | |
| 2.2.5 | Export audit JSON/CSV post-trasmissione | |

### 2.3 FIR digitali (`/segreteria/fir`)

| # | Verifica | ☐ |
|---|----------|---|
| 2.3.1 | Blocchi FIR — creazione, esaurimento progressivi | |
| 2.3.2 | Elenco formulari con stati badge WCAG | |

---

## 3. Operatore mobile (`/operatore/*`)

| # | Verifica | ☐ |
|---|----------|---|
| 3.1 | Dashboard operatore — titoli unificati in header | |
| 3.2 | Bottom nav — touch target ≥ 44px, badge bonifica | |
| 3.3 | Bonifica wizard — fasi, flash aria-live | |
| 3.4 | Ricambi — catalogo + upload foto bulk stub (max 10) | |
| 3.5 | Profilo — salvataggio con feedback visibile | |
| 3.6 | Operatore **non** accede a `/segreteria/*` | |

---

## 4. Palestra operativa (demo)

| # | Verifica | ☐ |
|---|----------|---|
| 4.1 | Toggle «Palestra ON» in sidebar — badge visibile | |
| 4.2 | Banner demo in top area con messaggio chiaro | |
| 4.3 | Dati produzione nascosti in scope demo | |
| 4.4 | Cross-write prod ↔ demo **negato** (ordini, MUD) | |
| 4.5 | Walkthrough demo su dashboard (se seeded) | |
| 4.6 | Reset demo / re-seed funzionante | |

---

## 5. Accessibilità & UX trasversale

| # | Verifica | ☐ |
|---|----------|---|
| 5.1 | Focus visibile su link, bottoni, input (Tab) | |
| 5.2 | Modali — chiusura Esc + focus trap | |
| 5.3 | Sidebar tablet 768–1024px — menu collassabile | |
| 5.4 | Copy errori validazione in italiano | |
| 5.5 | Empty states con CTA dove previsto | |

---

## 6. Firma accettazione

| Campo | Valore |
|-------|--------|
| Data sessione UAT | |
| Ambiente | staging / pre-prod |
| Partecipanti | |
| Esito complessivo | GO / GO con riserve / NO-GO |
| Bloccanti aperti | |

**Firma responsabile operativo:** _________________________

**Firma referente tecnico:** _________________________

---

## Riferimenti

- Piano ciclo 5: [CICLO-5-PIANO-360.md](CICLO-5-PIANO-360.md)
- UX guidelines: [UX-GUIDELINES.md](UX-GUIDELINES.md)
- Palestra: [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md)
- Go-live consolidato: [GO-LIVE-360.md](GO-LIVE-360.md)
