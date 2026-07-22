# Procedura submission template Meta — runbook operativo

> **Task:** go-live · **Scopo:** guida click-by-click per creare e far approvare i due template
> **obbligatori** (`appointment_reminder`, `review_request`) su WhatsApp Manager, con i blocchi da
> copiare, i motivi di rifiuto comuni e la verifica finale. **Aggiornato:** 2026-07-22.
>
> La **specifica canonica** (nomi, categorie, parametri) resta `docs/META-TEMPLATES.md`: questo file è
> il *come si fa*, quello è il *cosa deve contenere*. In caso di discrepanza, vince `META-TEMPLATES.md`.

---

## Prima di iniziare

- [ ] La **WABA** su cui lavori è collegata e verificata (in sandbox: Test WABA `1333683675528873`).
- [ ] Hai accesso a **WhatsApp Manager** (business.facebook.com → WhatsApp Manager) con ruolo adeguato.
- [ ] Sai su *quale* WABA stai creando i template: sono **per-WABA** (ADR-003), quindi ogni cliente che
      collega la propria WABA dovrà avere gli stessi template ricreati lì. In fase di lancio/demo basta
      la Test WABA; per un cliente reale, la sua.

> ⚠️ **Perché la categoria conta.** Meta approva più in fretta e fattura meno un template **UTILITY**
> (transazionale) di uno **MARKETING**. Mettere in MARKETING qualcosa di transazionale (o viceversa)
> è la **prima causa di rifiuto**. Rispetta le categorie della spec: reminder = UTILITY, recensione = MARKETING.

---

## Template 1 — `appointment_reminder` (UTILITY) · OBBLIGATORIO

Serve al flusso Promemoria & Scadenze: senza, i reminder non partono.

**Percorso:** WhatsApp Manager → *Modelli di messaggio* → **Crea modello**.

| Campo | Valore da impostare |
|---|---|
| **Categoria** | `Utilità` (UTILITY) |
| **Nome** | `appointment_reminder` ← *esatto, minuscolo, underscore. Il codice cerca questo nome.* |
| **Lingua** | `Italiano` |

**Corpo (Body)** — incolla:

```
Ciao {{1}}, ti ricordiamo il tuo appuntamento del {{2}} alle {{3}}.
Per qualsiasi necessità rispondi pure a questo messaggio.
```

**Esempi variabili (sample values)** — Meta li chiede per approvare:

| Variabile | Esempio |
|---|---|
| `{{1}}` | `Mario Rossi` |
| `{{2}}` | `03/07/2026` |
| `{{3}}` | `15:30` |

**Bottoni** → tipo **Risposta rapida** (Quick reply), due bottoni **in quest'ordine**:

| # | Testo |
|---|---|
| 1 | `Confermo` |
| 2 | `Disdico` |

> Il testo dei bottoni **è** il payload che il webhook riceve: il codice riconosce già `Confermo`/`Disdico`
> (case-insensitive) e aggiorna l'appuntamento a confermato/disdetto. Se usi label diverse, impostale poi
> in `/automations` (card Promemoria → bottoni conferma/disdetta).

---

## Template 2 — `review_request` (MARKETING) · serve per l'add-on Recensioni

Necessario solo se vendi/attivi l'add-on Recensioni. Richiede l'opt-in del contatto (il codice salta i non opt-in).

**Percorso:** *Crea modello*.

| Campo | Valore |
|---|---|
| **Categoria** | `Marketing` |
| **Nome** | `review_request` |
| **Lingua** | `Italiano` |

**Corpo (Body)** — incolla:

```
Ciao {{1}}, grazie per esserti affidato a noi!
Se ti va, lasciaci una recensione: bastano 30 secondi e per noi è preziosa. 🙏
```

**Esempio variabile:** `{{1}}` = `Mario Rossi`.

**Bottone** → tipo **Invito all'azione** → **Visita il sito web**:

- **Modalità consigliata — URL statico:** incolla l'**URL completo e fisso** della pagina recensioni Google
  dell'attività, es. `https://g.page/r/IL_TUO_PLACE_ID/review`. In `/automations` lascia la modalità **Statico**
  (default): il codice non passa alcun parametro. È la scelta naturale, dato che i template sono comunque per-WABA.

- **Alternativa — URL dinamico:** URL con parte variabile `https://g.page/r/{{1}}/review`; in `/automations`
  scegli **Dinamico** e inserisci il suffisso (es. il place id). Usala solo se vuoi un unico template riusabile.

> Come trovare il link recensioni Google: cerca l'attività su Google → scheda dell'attività → *Chiedi recensioni*
> → copia il link che genera (formato `g.page/r/.../review`).

---

## Inviare e attendere

1. Rivedi l'anteprima a destra: deve corrispondere ai blocchi qui sopra.
2. **Invia per la revisione.**
3. Stato in *Modelli di messaggio*: `In sospeso` → `Approvato` / `Rifiutato`. Tempi tipici: **da minuti a 24h**.
4. Ad approvazione, non serve altro lato codice: i nomi (`appointment_reminder`, `review_request`) sono già
   quelli che il codice invia.

---

## Se viene rifiutato — cause comuni e rimedi

| Motivo del rifiuto | Rimedio |
|---|---|
| **Categoria sbagliata** (reminder messo in Marketing, o promo in Utility) | Ricrea nella categoria giusta della spec. Non "correggere": crea nuovo. |
| **Sample values mancanti o incoerenti** col testo | Fornisci esempi realistici per ogni `{{n}}` (nome vero, data valida, ora valida). |
| **Parametri non sequenziali** (usi `{{1}}` e `{{3}}` saltando `{{2}}`) | Le variabili devono essere `{{1}},{{2}},{{3}}` consecutive, nell'ordine del testo. |
| **Testo troppo promozionale in un UTILITY** | Togli toni di vendita dal reminder; il reminder è transazionale. |
| **URL del bottone non valido / non raggiungibile** | Usa un link reale e apribile (testa il `g.page/.../review` nel browser). |
| **Placeholder tra parentesi nel testo** (es. `[nome]`) | Usa `{{1}}`, non testo segnaposto tuo. |

Dopo la correzione, reinvia: è normale un paio di iterazioni al primo template.

---

## Verifica dopo l'approvazione (smoke test)

Conferma che un template approvato parta davvero:

- **Via app (consigliato):** crea un appuntamento a breve su `/appointments` per un contatto **opted-in** con il
  tuo numero tester, poi forza/attendi il reminder. Controlla in `/messages` lo stato `sent`→`delivered`, e sul
  telefono l'arrivo con i due bottoni. Tocca `Confermo` → l'appuntamento passa a *confermato*.
- **Via script (sandbox):** `scripts/whatsapp-test-send.sh` per un invio diretto di prova dalla Test WABA.

> Se il reminder resta muto: verifica che (1) il template sia **Approvato**, (2) l'automazione Promemoria sia
> **attiva** in `/automations`, (3) il **cron `schedule:run`** giri sul VPS, (4) il **queue worker** sia attivo.

---

## Ripetere per ogni nuovo cliente (per-WABA)

Quando un cliente collega la sua WABA in `/whatsapp`, i template **non** si portano dietro: vanno ricreati nella
sua WABA con questa stessa procedura. Suggerimento onboarding: fallo tu insieme al cliente durante il setup, così
parti con `appointment_reminder` approvato dal giorno 1. Traccia lo stato nella checklist di `META-TEMPLATES.md`.
