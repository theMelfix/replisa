# Onboarding primo cliente — checklist step-by-step

> **Task:** E6.2.1 · **Scopo:** cosa fare, in ordine, dal "il cliente dice sì" fino ai flussi attivi e
> testati. **Aggiornato:** 2026-07-22.
>
> Collegati: `DEMO-SCRIPT.md` (prima), `META-TEMPLATES-SUBMISSION.md` (passo 4), `GO-LIVE.md` (infra prod).

---

## Panoramica — le 6 fasi

1. **Crea l'account** (censimento da admin *oppure* registrazione self-service)
2. **Raccogli i dati Meta** del cliente (WABA + numero + token)
3. **Collega il numero** in piattaforma e verifica la connessione
4. **Crea e fai approvare i template** nella WABA del cliente
5. **Configura i flussi** (testi, bottoni, offset, piano/settore)
6. **Test end-to-end** e consegna

> Tempo tipico: la parte in piattaforma è di minuti; il collo di bottiglia è l'**approvazione dei template
> Meta** (minuti → 24h) e, se il cliente non ha ancora una WABA, la **verifica business** lato Meta (più lunga).

---

## Fase 1 — Crea l'account

Due strade, scegli in base a chi fa cosa:

**A) Censimento da admin (consigliato per il primo cliente — lo guidi tu).**
- [ ] `/admin/tenants` → **Nuovo cliente**: nome attività, email owner, **settore**, P.IVA (opz.), licenza offline (opz.).
- [ ] L'owner riceve un'**email di invito** (URL firmato, 7 giorni) per impostare la password: `/invito/{user}`.
- [ ] Se il cliente paga offline / in modo concordato, assegna una **licenza offline** (`manual_plan`) col piano giusto:
      ha priorità su Stripe in `PlanLimits`, così parte già con i limiti corretti senza carta.

**B) Registrazione self-service.**
- [ ] Il cliente va su `/register`, inserisce dati attività + **dati fiscali** (ragione sociale, P.IVA, indirizzo,
      SDI/PEC) e **settore**. La P.IVA è validata (checksum + VIES).
- [ ] Parte la **prova gratuita di 14 giorni** (piano Pro durante il trial). Per continuare, andrà su `/billing`.

> **Settore:** è obbligatorio e determina quali **scadenze nazionali** vede (es. IMU/730 solo ai commercialisti).
> Impostalo corretto: si può cambiare da admin (`/admin/tenants`) ma meglio subito giusto.

---

## Fase 2 — Raccogli i dati Meta del cliente

Servono per collegare il suo numero (ADR-003: ogni cliente usa la **propria** WABA). Dal suo Business Manager:

- [ ] **Phone Number ID** (del numero WhatsApp business)
- [ ] **WABA ID** (WhatsApp Business Account ID)
- [ ] **Access token** (System User token permanente, scope `whatsapp_business_messaging` + `_management`)

> Se il cliente **non ha ancora** una WABA/numero business: va creato il numero su WhatsApp Business Platform e
> completata la **verifica business** lato Meta. È il passo più lento — avvialo per primo se manca.
> (Futuro: Embedded Signup self-service — ICE-11 — ridurrà questo a ~5 min.)

---

## Fase 3 — Collega il numero in piattaforma

- [ ] Accedi come l'owner (o fatti dare accesso) → `/whatsapp` (**Account WhatsApp**).
- [ ] Inserisci **Phone Number ID**, **WABA ID**, **Access token** → salva. Il token è criptato a riposo e non
      viene più ri-mostrato (campo vuoto = invariato).
- [ ] Premi **Verifica connessione**: interroga in live la Graph API di Meta. Deve dare esito positivo.

> Se la verifica fallisce: token scaduto/scope errati, o Phone Number ID/WABA ID sbagliati. Rigenera il token
> (System User, expiration Never) e ricontrolla gli ID.

---

## Fase 4 — Template Meta nella WABA del cliente

Segui **`META-TEMPLATES-SUBMISSION.md`** (runbook click-by-click), sulla **WABA del cliente**:

- [ ] `appointment_reminder` (UTILITY) — obbligatorio per i promemoria.
- [ ] `review_request` (MARKETING) — solo se attivi l'add-on Recensioni.
- [ ] *(campagne/scadenze)* almeno un template MARKETING/UTILITY approvato, se il cliente li userà.
- [ ] Attendi lo stato **Approvato** in WhatsApp Manager.

> Fallo **insieme al cliente** durante il setup: i template sono per-WABA, e partire con `appointment_reminder`
> già approvato evita che i primi reminder restino muti.

---

## Fase 5 — Configura i flussi

Da `/automations` e dalle sezioni collegate:

- [ ] **Benvenuto:** attiva → personalizza saluto, header/footer e i 3 bottoni con le risposte del cliente.
- [ ] **Promemoria & Scadenze:** attiva → imposta template (`appointment_reminder`), lingua, anticipi (es. `24, 2`)
      e le label dei bottoni (devono coincidere col template approvato).
- [ ] **Campagne:** se le userà, verifica in `/campaigns` il nome del template approvato.
- [ ] **Scadenze:** in `/scadenze`, attiva i promemoria sulle scadenze pertinenti al suo settore (+ le sue proprie).
- [ ] **Recensioni (add-on):** se incluso/attivo, in `/automations` imposta il link recensione (statico consigliato).
- [ ] **Appuntamenti:** popola i primi appuntamenti in `/appointments` (manuale o **import CSV** `telefono,nome,data`),
      così i promemoria hanno su cosa lavorare.

---

## Fase 6 — Test end-to-end e consegna

- [ ] **Benvenuto:** dal telefono del cliente (o un tester), scrivi al suo numero → arriva il menu coi bottoni.
- [ ] **Promemoria:** crea un appuntamento a breve per un contatto opt-in → verifica il reminder sul telefono e
      lo stato `sent`→`delivered` in `/messages`; tocca `Confermo` → l'appuntamento passa a *confermato*.
- [ ] **Dashboard:** mostra al cliente `/dashboard` (numeri, grafico, costo stimato) e `/contacts` (conversazioni).
- [ ] **Formazione lampo:** mostra dove attiva/disattiva i flussi e dove vede i messaggi. 10 minuti bastano.
- [ ] **Contatti utili:** lascia riferimento assistenza (`info@giovannimelfi.it`) e link legali.

> Prerequisiti infra perché i flussi girino in prod (vedi `GO-LIVE.md`): **queue worker** attivo e **cron
> `schedule:run`** sul VPS. Senza, il webhook non processa e i reminder non partono.

---

## Dopo l'onboarding

- [ ] Segna il cliente come **Cliente** nella griglia (`PMI-TARGET.md`) / lead in `/admin/leads`.
- [ ] Dopo qualche giorno: **raccogli feedback** (E6.2.5) — base per il primo testimonial.
- [ ] Verifica che il primo ciclo di billing (o la licenza offline) sia coerente con l'accordo.

---

## Checklist rapida (copia-incolla)

```
[ ] Account creato (admin censimento / self-service) + settore corretto
[ ] Piano/licenza impostati (offline o Stripe/trial)
[ ] Dati Meta raccolti (phone_number_id, waba_id, access_token)
[ ] Numero collegato in /whatsapp + Verifica connessione OK
[ ] appointment_reminder APPROVATO nella WABA del cliente
[ ] (se add-on) review_request APPROVATO
[ ] Flussi attivati e configurati (/automations, /scadenze)
[ ] Primi appuntamenti caricati (/appointments)
[ ] Test: benvenuto OK, reminder OK + conferma OK
[ ] Infra prod: worker + cron attivi
[ ] Cliente formato + contatti assistenza lasciati
```
