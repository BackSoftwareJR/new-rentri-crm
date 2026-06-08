# Sprint 88 — Audit notes: payload_firmato xFIR vs COSE/MASE

**Data audit:** 4 giugno 2026  
**Scope:** shape `payload_firmato` in `RentriXfirTrasmissioneRequest` vs COSE_Sign1 ministeriale RENTRI.

---

## 1. Riferimenti

| Artefatto | Path |
|-----------|------|
| Request adapter | `app/Services/Rentri/Dto/RentriXfirTrasmissioneRequest.php` |
| COSE signer | `app/Services/Rentri/RentriXfirCoseSigner.php` |
| Mapper trasmissione | `app/Services/Rentri/RentriXfirCoseTransmissionMapper.php` |
| Fixture COSE MASE | `tests/fixtures/rentri/mase/xfir-cose-sign1.json` |
| Fixture trasmissione | `tests/fixtures/rentri/mase/xfir-trasmissione.json` |

---

## 2. Shape attesa MASE (COSE_Sign1)

Campi **obbligatori** in `payload_firmato`:

| Campo | Tipo | Note |
|-------|------|------|
| `typ` | string | Sempre `COSE_Sign1` |
| `protected` | string | Base64url header COSE (`alg`, `typ`) |
| `payload` | string | Base64url xFIR XML/JSON canonicalizzato |
| `signature` | string | Firma HMAC stub o RS256/ES256 live |

Campo **opzionale** top-level:

| Campo | Tipo | Note |
|-------|------|------|
| `alg` | string | `STUB-HMAC-SHA256`, `RS256`, `ES256` — duplica header protected |

Header `protected` decodificato deve contenere almeno `alg` e `typ: COSE_Sign1`.

---

## 3. Output signer (pre-fix audit)

`RentriXfirCoseSigner` produce correttamente i 5 campi COSE.

`RentriFirSigningService` aggiunge metadati CRM al JSON persistito:

| Campo | Scopo | Inviabile a MASE? |
|-------|-------|-------------------|
| `api_mode` | stub/live runtime | ❌ |
| `numero_fir` | audit locale | ❌ |
| `firmato_at` | timestamp firma CRM | ❌ |
| `stub` | flag dev/test | ❌ |

---

## 4. Mismatch documentato

**M-88-1:** `RentriXfirTrasmissioneRequest::body()` passava l'intero `xfir_signed_payload` come `payload_firmato`, includendo metadati CRM non previsti dal contratto MASE.

**Impatto:** body HTTP verso `POST .../xfir/trasmissione` poteva contenere chiavi extra (`api_mode`, `numero_fir`, `firmato_at`, `stub`).

**M-88-2:** Fixture Sprint 84 `xfir-trasmissione.json` esempio incompleto (mancavano `protected`, `alg`).

---

## 5. Fix applicato (Sprint 88)

1. **`RentriXfirCoseTransmissionMapper::forTransmission()`** — estrae solo chiavi COSE MASE.
2. **`RentriXfirTrasmissioneRequest::body()`** — usa mapper su `payload_firmato`.
3. **Fixture** — `xfir-cose-sign1.json` + aggiornamento esempio in `xfir-trasmissione.json`.
4. **Test Sprint 88** — 7 test contract/structure in `tests/Feature/Sprint88/*`.

Nessuna modifica al signer COSE (output già conforme). Metadati CRM restano in `xfir_signed_payload` per download/audit locale.

---

## 6. Conformità post-fix

| Check | Esito |
|-------|-------|
| Signer stub → 5 campi COSE | ✅ |
| Protected header → COSE_Sign1 + alg | ✅ |
| Trasmissione → no metadati CRM | ✅ |
| Root `typ` = `payload_firmato.typ` | ✅ |
| Regression Sprint 39/84 | ✅ |

---

## 7. Residui (non bloccanti)

- Validazione live RS256/ES256 vs keystore reale MASE — coperta da test stub; live richiede certificato operatore in sandbox.
- `payload` interno (xFIR XML) — già validato da `RentriXfirValidator` pre-firma; fuori scope shape COSE envelope.
