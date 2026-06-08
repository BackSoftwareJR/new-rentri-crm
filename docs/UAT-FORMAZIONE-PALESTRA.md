# UAT formazione — Palestra operativa

Runbook per **sessione guidata** segreteria/admin sulla palestra operativa RENTRI.  
Durata stimata: **90 minuti** (teoria 15 min + pratica 60 min + verifica 15 min).

Riferimenti: [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) · [GO-LIVE-CICLO-4.md](GO-LIVE-CICLO-4.md) · [CICLO-4-PIANO.md](CICLO-4-PIANO.md)

---

## 1. Prerequisiti

| Requisito | Verifica |
|-----------|----------|
| Utente **segreteria** o **admin** | Login `segreteria@example.com` (staging) |
| `ALLOW_SESSION_DEMO=true` su istanza formazione | Toggle sidebar visibile, non 🔒 |
| Certificato PKCS#12 **sandbox MASE** | File `.p12` valido per demoapi |
| Fixture demo (opzionale) | `php artisan rentri:demo-seed` eseguito |
| Browser desktop (Chrome/Firefox) | Livewire + sidebar |

**Non usare** certificati produzione durante la formazione.

---

## 2. Agenda sessione

| Orario | Attività | Obiettivo |
|--------|----------|-----------|
| 0:00–0:15 | Introduzione palestra vs deploy demo | Capire scope `is_demo`, sandbox MASE |
| 0:15–0:25 | Toggle ON + banner | Attivare/disattivare session demo |
| 0:25–0:40 | Impostazioni RENTRI demo | Preset multi-operatore, cert, test connessione |
| 0:40–0:55 | Walkthrough dashboard | 6 step, progress bar, deep link |
| 0:55–1:15 | Flusso operativo | Blocchi → trasporto → vidima → firma → registro |
| 1:15–1:25 | Isolamento dati | Verificare assenza dati prod in palestra |
| 1:25–1:30 | Chiusura + checklist | Firma partecipanti |

---

## 3. Script facilitatore (passo-passo)

### 3.1 Introduzione (15 min)

Spiegare al gruppo:

1. **Palestra operativa** = toggle sessione su istanza condivisa (staging/formazione).
2. **Istanza demo deploy** (`APP_DEMO_MODE=true`) = ambiente separato, alternativa alla palestra.
3. In palestra: API solo **demoapi.rentri.gov.it**; dati prod **non visibili**.
4. **Disattivare sempre** la palestra prima di operazioni produzione reali.

Distribuire [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) §6 FAQ.

### 3.2 Attivazione toggle (10 min)

1. Login come segreteria → `/segreteria`
2. Sidebar → **Palestra operativa** → clic **OFF**
3. Modale → **Attiva demo** → confermare
4. Verificare banner: «scope demo attivo in sessione»
5. **Esercizio:** disattivare (ON → OFF implicito) e riattivare

**Domanda al gruppo:** l'operatore mobile vede il toggle? (Risposta attesa: **no**.)

### 3.3 Impostazioni RENTRI sandbox (15 min)

1. Link sidebar → **Impostazioni RENTRI demo**
2. Selezionare profilo **Sede Nord — formazione**
3. **Applica preset sandbox** → verificare `DEMO-SITE-NORD-001`
4. Step 2 wizard → upload certificato sandbox `.p12`
5. **Test connessione sandbox** → badge connessione OK (o stub se cert assente)
6. Campo **Note operatore** → salvare promemoria formazione

### 3.4 Walkthrough dashboard (10 min)

1. Tornare a **Dashboard** → card «Prova flusso RENTRI»
2. Mostrare **barra progresso** (X/6)
3. Clic step 2 → deep link `?step=2` certificato
4. Se cert in scadenza: mostrare hint giallo

Comando alternativo: `php artisan rentri:demo-seed` se fixture assenti.

### 3.5 Flusso RENTRI end-to-end (20 min)

Con fixture seed caricate:

| Step | Azione | Verifica |
|------|--------|----------|
| 3 | `/segreteria/fir/blocchi` | Blocco `DEMO-BLK-001` presente |
| 4 | Dettaglio trasporto demo | Stato «in preparazione» |
| 5 | Vidima FIR → Firma xFIR | Protocollo + badge firmato |
| 6 | `/segreteria/rentri` | Trasmissione movimento bozza |

Se sandbox offline: spiegare stub locale e messaggio UI esplicito.

### 3.6 Verifica isolamento (10 min)

Con palestra **ON**:

1. Dashboard KPI e-commerce/MUD → solo dati demo (o zero)
2. Anagrafiche → nessuna anagrafica prod visibile
3. Magazzino → solo CER collegati a demo; kg da movimenti demo

Disattivare palestra → KPI produzione ripristinati.

---

## 4. Checklist firmabile — UAT palestra operativa

**Istanza / data formazione:** _________________________________  
**Facilitatore:** _________________________________  
**Versione CRM / ciclo 4:** Sprint 50 — chiusura ciclo 4

| # | Verifica | OK | NOK | Note |
|---|----------|:--:|:---:|------|
| 1 | Toggle attivazione/disattivazione funzionante | ☐ | ☐ | |
| 2 | Banner scope demo visibile in sessione | ☐ | ☐ | |
| 3 | Operatore mobile **non** accede al toggle | ☐ | ☐ | |
| 4 | Preset multi-operatore applicato (profilo selezionato) | ☐ | ☐ | |
| 5 | Certificato sandbox caricato o stub compreso | ☐ | ☐ | |
| 6 | Walkthrough 6 step + progress bar compresi | ☐ | ☐ | |
| 7 | Flusso vidima → firma → registro completato (o stub) | ☐ | ☐ | |
| 8 | Dati produzione non visibili con palestra ON | ☐ | ☐ | |
| 9 | Disattivazione palestra ripristina scope prod | ☐ | ☐ | |
| 10 | FAQ [PALESTRA-OPERATIVA.md](PALESTRA-OPERATIVA.md) consegnata | ☐ | ☐ | |

### Firme partecipanti

| Nome | Ruolo | Firma | Data |
|------|-------|-------|------|
| | Segreteria | | |
| | Segreteria | | |
| | Admin / IT | | |
| | Facilitatore | | |

**Esito sessione:** ☐ Superata  ☐ Con riserve  ☐ Non superata

**Riserve / azioni correttive:**

_________________________________________________________________

_________________________________________________________________

---

## 5. Automazione di riferimento

Verifica tecnica pre/post sessione:

```bash
php artisan test --filter=Sprint47
php artisan test --filter=Sprint48
php artisan test --filter=Sprint49
npm run test:e2e                    # smoke Playwright palestra
php artisan rentri:demo-preflight   # su istanza demo deploy
```

---

*UAT formazione palestra — Ciclo 4 Sprint 50.*
