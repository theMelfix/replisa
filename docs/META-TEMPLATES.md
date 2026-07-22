# Template WhatsApp da sottomettere a Meta

> Specifica canonica dei **message template** che Replisa invia via Meta Cloud API.
> Ogni template va creato e approvato **nel WhatsApp Manager della WABA**.
>
> ⚠️ **Multi-tenant (ADR-003):** ogni tenant collega la *propria* WABA (`waba_id` +
> `access_token`, vedi `WhatsAppSettings`). I template **non sono globali**: vivono
> per-WABA e vanno ricreati/approvati in **ciascuna** WABA del cliente. Questo file è
> la **specifica di riferimento** (nome, categoria, lingua, struttura, parametri) da
> replicare identica ovunque, perché il codice si aspetta nomi e ordine dei parametri
> precisi. In fase sandbox: crearli nella **Test WABA** `1333683675528873`.
>
> Lingua di default per tutti: **`it`** (italiano). Il `language.code` è impostato dal
> codice (`config['language'] ?? 'it'`).

---

## Riepilogo

| Template (name) | Categoria | Usato da | Parametri body | Bottoni |
|---|---|---|:--:|---|
| `appointment_reminder` | **UTILITY** | `AppointmentReminder` (E3.2) | `{{1}}` nome, `{{2}}` data, `{{3}}` ora | 2 quick-reply (conferma / disdici) |
| `review_request` | **MARKETING** | `ReviewRequest` (E3.3, add-on) | `{{1}}` nome | 1 URL (statico **o** dinamico) |
| *(scelto dal tenant)* | **MARKETING** | `Campaigns` / `SendCampaign` (E3.4) | opzionale `{{1}}` nome | a piacere |
| *(scelto dal tenant)* | **MARKETING/UTILITY** | `SendDeadlineReminders` (E3.2.6) | opzionale `{{1}}` nome | a piacere |
| `hello_world` | UTILITY (preesistente Meta) | test invio sandbox | — | — |
| *(nessuno)* | — | Welcome Flow (E3.1) | — | free-form, **niente template** |

> **Welcome Flow:** parte sempre **dentro** la finestra di servizio 24h (risposta al
> primo messaggio del contatto) → usa messaggi free-form interattivi (`sendButtons`),
> **non** richiede alcun template approvato.

---

## 1. `appointment_reminder` — Promemoria appuntamento

- **Name:** `appointment_reminder`
- **Category:** `UTILITY` (transazionale: promemoria di un appuntamento già preso)
- **Language:** `Italian (it)`
- **Codice di riferimento:** `App\Services\Automation\AppointmentReminder::components()`

### Struttura

**Body** (3 variabili, in quest'ordine esatto):

```
Ciao {{1}}, ti ricordiamo il tuo appuntamento del {{2}} alle {{3}}.
Per qualsiasi necessità rispondi pure a questo messaggio.
```

| Variabile | Contenuto (dal codice) | Esempio |
|---|---|---|
| `{{1}}` | nome del contatto (`contact.name`, fallback `Cliente`) | `Mario Rossi` |
| `{{2}}` | data `d/m/Y` | `03/07/2026` |
| `{{3}}` | ora `H:i` | `15:30` |

**Sample values da fornire a Meta in fase di submission:** `Mario Rossi`, `03/07/2026`, `15:30`.

**Buttons:** 2 × **Quick reply**

| # | Testo bottone consigliato | → stato appuntamento |
|---|---|---|
| 0 | `Confermo` | `confirmed` |
| 1 | `Disdico` | `cancelled` |

> ✅ **Payload quick-reply allineati (2026-06-29).** Per i bottoni quick-reply *dei
> template* il webhook restituisce `button.payload` **uguale al testo del bottone**.
> Il codice (`handleButtonReply()`) ora ha come default proprio le label italiane
> **`Confermo` / `Disdico`** (`DEFAULT_CONFIRM_PAYLOAD`/`DEFAULT_CANCEL_PAYLOAD`), con
> match **case-insensitive**; restano accettati come fallback anche i vecchi
> `CONFIRM`/`CANCEL`. Quindi un template con bottoni `Confermo`/`Disdico` funziona
> **senza configurazione aggiuntiva**. Se usi label diverse, impostale comunque
> nell'`Automation.config` del tenant (`confirm_payload`/`cancel_payload`).

---

## 2. `review_request` — Richiesta recensione (add-on)

- **Name:** `review_request`
- **Category:** `MARKETING` (richiede opt-in del contatto; il codice salta i non opt-in)
- **Language:** `Italian (it)`
- **Codice di riferimento:** `App\Services\Automation\ReviewRequest::components()`
- **Gating:** add-on Recensioni (incluso nel piano **Business** o `tenants.reviews_addon`)

### Struttura

**Body** (1 variabile):

```
Ciao {{1}}, grazie per esserti affidato a noi!
Se ti va, lasciaci una recensione: bastano 30 secondi e per noi è preziosa. 🙏
```

| Variabile | Contenuto | Esempio |
|---|---|---|
| `{{1}}` | nome del contatto (fallback `Cliente`) | `Mario Rossi` |

**Sample value:** `Mario Rossi`.

**Button:** 1 × **Call-to-action → Visit website**

La scelta tra le due varianti si fa **dall'app**, in `/automations` → card *Richiesta
recensione* (visibile quando l'add-on è attivo), campo **"Link recensione nel template"**:

- **Statico (default, consigliato):** il bottone del template ha l'**URL completo e
  fisso** della pagina recensioni Google dell'attività, es.
  `https://g.page/r/IL_TUO_PLACE_ID/review`. In UI si lascia la modalità *statico* →
  `Automation.config.review_url_param` resta **null** e il codice **non** passa alcun
  componente button. ✅ Sempre valido, nessun parametro. Dato che i template sono
  comunque per-WABA (ADR-003), un URL fisso per attività è la scelta naturale.

- **Dinamico:** template con suffisso variabile, es. base `https://g.page/r/{{1}}/review`.
  In UI si seleziona *dinamico* e si inserisce il **suffisso** (es. il place id): il
  codice lo passa come `{{1}}` del bottone (`button`, `sub_type=url`, `index=0`).
  Permette un template unico riusabile cambiando solo il parametro per tenant.

> ✅ **Coerenza garantita dalla UI (2026-06-29).** La pagina Automazioni impedisce gli
> stati incoerenti che fallirebbero a runtime: in *statico* il param è forzato a null
> (niente "param di troppo"); in *dinamico* il suffisso è **obbligatorio** e validato
> (niente "param mancante"). Vedi `Automations::saveReviewSettings()`. L'unico residuo
> non verificabile lato app — modalità dichiarata diversa dal template reale su Meta —
> richiederebbe l'introspezione del template via Graph API (follow-up).

---

## 3. Campagne — template MARKETING scelto dal tenant

- **Name:** *libero* — lo sceglie il tenant in `/campaigns` (campo "Template Meta").
- **Category:** `MARKETING`
- **Codice di riferimento:** `App\Jobs\SendCampaign::nameComponents()`

Le campagne non hanno un template fisso: il tenant indica il **nome di un template già
approvato** nella sua WABA. Il flag "personalizza con il nome" (`include_name`) decide
se passare `{{1}}` = nome contatto nel body.

### Template di esempio (da fornire come starter ai clienti)

- **Name:** `promo_generica`
- **Category:** `MARKETING`, **Language:** `it`

**Body:**

```
Ciao {{1}}! 🎉 Questa settimana abbiamo una promozione pensata per te.
Passa a trovarci o rispondi a questo messaggio per maggiori informazioni.
```

| Variabile | Contenuto | Esempio |
|---|---|---|
| `{{1}}` | nome contatto (solo se `include_name=true`) | `Mario` |

> Se il tenant **non** spunta "personalizza con il nome", usare un template **senza
> variabili** (Meta rigetta un template con `{{1}}` se non passi il parametro).

---

## 4. Promemoria scadenze — template scelto dal tenant

- **Name:** *libero* — impostato in `/scadenze` per ogni promemoria (`DeadlineReminder.template_name`).
- **Category:** `UTILITY` o `MARKETING` (a seconda del contenuto; le scadenze fiscali
  generiche sono tipicamente UTILITY, le comunicazioni promozionali MARKETING).
- **Codice di riferimento:** `App\Console\Commands\SendDeadlineReminders` → riusa `SendCampaign`.

Funziona come le campagne (riusa `SendCampaign`): `include_name` → `{{1}}` = nome.

### Template di esempio

- **Name:** `scadenza_generica`, **Language:** `it`

**Body:**

```
Ciao {{1}}, ti ricordiamo una scadenza in arrivo. Contattaci per organizzarti per tempo.
```

> Per le scadenze fiscali nazionali (IMU, 730, …) conviene un template **UTILITY**
> dedicato e specifico per scadenza, per massimizzare le probabilità di approvazione e
> la pertinenza (es. `scadenza_imu_acconto`).

---

## 5. `hello_world` (test)

Template di sistema preesistente di Meta, usato per il primo invio di prova in sandbox
(`scripts/whatsapp-test-send.sh`). Nessuna azione: già disponibile.

---

## Procedura di submission (per ogni WABA)

> 📋 **Runbook operativo click-by-click:** `docs/META-TEMPLATES-SUBMISSION.md` (blocchi da copiare,
> cause di rifiuto comuni, verifica post-approvazione). Qui sotto la versione sintetica.

1. **WhatsApp Manager** → *Account WhatsApp Business* → WABA del tenant → **Modelli di messaggio** → *Crea modello*.
2. Impostare **Categoria** e **Lingua** (`Italiano`) come da specifica sopra.
3. Incollare il **Body** con i `{{n}}` nell'ordine indicato e fornire i **sample values**.
4. Aggiungere i **bottoni** come specificato (quick-reply per `appointment_reminder`, URL per `review_request`).
5. Inviare per l'approvazione. Tempi tipici: minuti → 24h. Stato visibile in WhatsApp Manager.
6. Una volta **APPROVED**, il `name` del template è già quello atteso dal codice
   (`appointment_reminder`, `review_request`) o quello inserito dal tenant in UI
   (campagne / scadenze).

### Checklist nomi attesi dal codice

- [ ] `appointment_reminder` — UTILITY, `it`, body 3 var, 2 quick-reply
- [ ] `review_request` — MARKETING, `it`, body 1 var, 1 URL button
- [ ] *(campagne)* almeno un template MARKETING approvato, nome inserito in `/campaigns`
- [ ] *(scadenze)* almeno un template approvato, nome inserito in `/scadenze`

---

## Action item lato codice (collegati ai template)

- [x] **Payload quick-reply reminder:** ✅ fatto 2026-06-29 — default `AppointmentReminder`
  portati a `Confermo`/`Disdico`, match case-insensitive con fallback `CONFIRM`/`CANCEL`.
  Un template con bottoni `Confermo`/`Disdico` funziona out-of-the-box. → vedi §1.
- [x] **Review URL:** ✅ fatto 2026-06-29 — scelta statico/dinamico esposta in
  `/automations` (card Recensioni) con validazione in `Automations::saveReviewSettings()`:
  statico → param null, dinamico → suffisso obbligatorio. Gli stati incoerenti
  controllabili dall'app sono chiusi. Follow-up (opzione 3): introspezione del template
  Meta via Graph API per validare la modalità rispetto al template reale. → vedi §2.