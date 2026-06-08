# Palestra operativa — Guida utente

La **palestra operativa** permette di esercitarsi sul flusso RENTRI (vidima FIR, firma xFIR, registro) in **sandbox MASE** senza toccare i dati di produzione.

---

## 1. Attivazione

1. Accedi come **segreteria** o **admin**
2. Nella **sidebar sinistra**, sezione «Palestra operativa»
3. Clic **OFF** → conferma nel modale → passa a **ON**
4. Compare il banner giallo «Palestra operativa — scope demo attivo in sessione»

Per disattivare: clic **ON** → torni allo scope produzione (`is_demo=false`).

> Su ambienti **production** il toggle è bloccato salvo configurazione esplicita `ALLOW_SESSION_DEMO=true` da parte dell'amministratore di sistema.

---

## 2. Cosa cambia in demo

| Aspetto | Produzione (OFF) | Palestra (ON) |
|---------|------------------|---------------|
| Dati FIR/registro/trasporti scoped | `is_demo=false` | `is_demo=true` |
| API MASE | Da impostazioni (sandbox/prod) | **Solo demoapi.rentri.gov.it** |
| Impostazioni RENTRI | Row produzione | Row demo separata |
| Walkthrough dashboard | Nascosto | 6 step con stato completamento |
| Anagrafiche / VFU / magazzino CER | Scope produzione | Solo record `is_demo=true` o collegati a demo |
| Note operatore RENTRI | — | Campo libero visibile in impostazioni demo |

I dati produzione **non sono visibili né modificabili** finché la palestra è attiva (scope Eloquent + policy autorizzazione).

---

## 3. Configurare RENTRI sandbox

1. Con palestra **ON**, apri **Impostazioni RENTRI** (link rapido in sidebar o menu)
2. **Applica preset sandbox** — seleziona **profilo operatore** (sede principale, Nord, Sud) e applica; CF operatore e num_iscr_sito compilati automaticamente
3. **Carica certificato PKCS#12** sandbox MASE (una tantum) — controlla il badge **scadenza certificato** (verde = valido, rosso = scaduto)
4. **Note operatore** (opzionale) — annotazioni interne visibili solo in scope demo
5. **Test connessione sandbox** — verifica health + codifiche CER su demoapi

MASE **non usa API key** nel software: l'autenticazione è via certificato mTLS caricato dall'operatore.

Senza certificato le chiamate restano in **stub locale** (messaggio esplicito in UI). Il walkthrough dashboard segnala lo step certificato come incompleto finché manca un PKCS#12 valido e non scaduto.

---

## 4. Percorso consigliato

```
Toggle ON → rentri:demo-seed (o card dashboard) → Impostazioni RENTRI
→ Blocchi FIR → Trasporto demo → Vidima → Firma xFIR → Registro
```

La **card walkthrough** in dashboard elenca 6 step con **barra di progresso**, link diretti (`?step=2` per certificato) e spunta quando completati.

Comandi utili (segreteria/admin):

```bash
php artisan rentri:demo-seed          # fixture walkthrough
php artisan rentri:demo-seed --fresh  # rigenera scenario
php artisan rentri:demo-reset         # elimina solo dati demo
```

---

## 5. Sicurezza

- Disattivare la palestra **prima** di operazioni produzione reali
- Non condividere certificati sandbox e produzione
- L'operatore mobile **non** ha accesso al toggle (solo segreteria/admin)

Vedi [SECURITY-CHECKLIST-DEMO-PROD.md](SECURITY-CHECKLIST-DEMO-PROD.md).

---

## 6. FAQ operatore

### Perché non vedo anagrafiche / VFU / serbatoi di produzione con la palestra ON?

I moduli di formazione (anagrafiche, registrazioni VFU, codici CER in magazzino) rispettano lo stesso scope `is_demo` dei FIR. In session demo compaiono solo record demo o collegati a svuotamenti demo. Disattiva la palestra per tornare ai dati reali.

### Il toggle è grigio: cosa fare?

Su istanze `APP_ENV=production` serve `ALLOW_SESSION_DEMO=true` nel `.env` (solo staging o demo condivise autorizzate). In locale/staging il toggle è disponibile per segreteria e admin.

### Ho applicato il preset ma le chiamate MASE restano in stub

Verifica: (1) palestra ON, (2) certificato PKCS#12 caricato sulla row **demo** di Impostazioni RENTRI, (3) certificato **non scaduto** (badge in UI), (4) test connessione sandbox OK. Senza cert valido il client usa stub locale per evitare errori silenziosi.

### Posso usare lo stesso certificato sandbox e produzione?

No. Sandbox e produzione hanno certificati MASE distinti. Carica solo il PKCS#12 sandbox mentre la palestra è attiva.

### Cosa succede ai dati demo se disattivo la palestra?

Restano nel database con `is_demo=true` ma non sono più visibili né modificabili finché non riattivi la palestra. Usa `rentri:demo-reset` per eliminarli.

### L'operatore mobile vede la palestra?

No. Solo **segreteria** e **admin** possono attivare la session demo e vedere il walkthrough. L'app operatore continua sullo scope produzione.

### Le note operatore in Impostazioni RENTRI sono visibili in produzione?

Il campo **note operatore** è legato alla row impostazioni dello scope corrente: in palestra si salva sulla row demo; in produzione non compare nel flusso demo.

---

*Palestra operativa — Ciclo 4 Sprint 48.*
