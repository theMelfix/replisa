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

> ⚠️ **Vincolo payload quick-reply.** Per i bottoni quick-reply *dei template*, il
> webhook restituisce `button.payload` **uguale al testo del bottone**. Il codice
> (`handleButtonReply()`) confronta il payload con i default **`CONFIRM` / `CANCEL`**.
> Quindi, se il testo del bottone è `Confermo`/`Disdico`, bisogna **allineare** il
> matching in uno dei due modi:
> 1. impostare nell'`Automation.config` del tenant `confirm_payload = "Confermo"` e
>    `cancel_payload = "Disdico"`; **oppure**
> 2. cambiare i default in `AppointmentReminder` (`DEFAULT_CONFIRM_PAYLOAD`/
>    `DEFAULT_CANCEL_PAYLOAD`).
>
> In alternativa, usare come testo bottone letteralmente `CONFIRM`/`CANCEL` (brutto in
> UI). **Raccomandato:** opzione 1/2 con label italiane. → vedi action item in fondo.

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

Due varianti, a seconda della configurazione del tenant (`Automation.config.review_url_param`):

- **Statico (consigliato per partire):** URL fisso alla pagina recensioni Google del
  tenant, es. `https://g.page/r/IL_TUO_PLACE_ID/review`. **Nessun** componente button
  passato dal codice (se `review_url_param` è vuoto). ✅ Più semplice, sempre valido.

- **Dinamico:** URL con suffisso variabile, es. base
  `https://g.page/r/{{1}}/review`. Il codice passa `review_url_param` come `{{1}}` del
  bottone (component `button`, `sub_type=url`, `index=0`).
  ⚠️ Se scegli il template **dinamico**, il tenant **deve** avere `review_url_param`
  valorizzato, altrimenti Meta rigetta l'invio (variabile button mancante).

> **Nota multi-tenant:** essendo l'URL recensioni specifico di ogni attività, il
> template statico va comunque personalizzato per-WABA. La variante dinamica permette
> un template unico riusabile cambiando solo il parametro per tenant.

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

- [ ] **Payload quick-reply reminder:** allineare i default `CONFIRM`/`CANCEL` ai testi
  bottone italiani (`Confermo`/`Disdico`) — o nel codice (`AppointmentReminder`) o via
  `Automation.config` per-tenant. Senza questo, le risposte al reminder non aggiornano
  lo stato dell'appuntamento. → vedi §1.
- [ ] **Review URL:** decidere variante statica vs dinamica del button `review_request`
  e, se dinamica, garantire che `review_url_param` sia sempre valorizzato prima
  dell'invio (validazione in `WhatsAppSettings`/`Automations`). → vedi §2.