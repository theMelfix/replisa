# Script demo live — Replisa (5 minuti)

> **Task:** E5.2.3 · **Obiettivo:** mostrare in ~5 minuti il valore di Replisa a un titolare di PMI,
> chiudendo con una call-to-action. **Formato:** demo guidata su schermo (desktop o tablet).
> **Aggiornato:** 2026-07-22 — allineato ai flussi attuali (Benvenuto → Promemoria & Scadenze →
> Campagne, con Recensioni come add-on). Sostituisce l'ordine storico "Welcome → Reminder → Recensione".

---

## Prima della demo (checklist 2 minuti)

Da preparare **prima** che il cliente sia davanti, per non bruciare tempo in setup:

- [ ] Tenant demo già creato e loggato (`/dashboard`), con un numero WhatsApp sandbox collegato (`/whatsapp`).
- [ ] Il **tuo telefono** (o quello del cliente, se è un tester) pronto con WhatsApp aperto sulla chat del numero demo.
- [ ] Almeno **3-4 contatti** e **8-10 messaggi** già presenti, così la dashboard e il grafico non sono vuoti.
- [ ] Un **appuntamento** creato tra ~2 ore (per mostrare il reminder che scatta) e uno **completato** ieri.
- [ ] Le automazioni **Benvenuto** e **Promemoria & Scadenze** già **attive**.
- [ ] Template Meta `appointment_reminder` approvato sul WABA demo (altrimenti il reminder resta in coda).
- [ ] Landing (`replisa.com`) aperta in una scheda separata, pronta per la chiusura.

> **Regola d'oro:** la demo mostra **il risultato sul telefono**, non i menu di configurazione. Il cliente
> deve vedere il messaggio WhatsApp arrivare, non ascoltare come si imposta un offset.

---

## Minuto 0:00–0:30 — Aggancio (il problema)

> *"Quanti messaggi WhatsApp ricevi al giorno dai clienti? E quante volte ti dimentichi di rispondere,
> o un cliente non si presenta a un appuntamento? Replisa fa lavorare WhatsApp al posto tuo — ti mostro
> come in cinque minuti, con esempi veri."*

Personalizza il problema sul settore del cliente:
- **Ristorante / pizzeria:** prenotazioni, conferme, no-show.
- **Studio medico / dentistico:** promemoria appuntamenti, disdette last-minute.
- **Palestra / centro estetico:** rinnovi abbonamento, scadenze.
- **Commercialista / assicurazione:** scadenze fiscali (IMU, 730), rinnovi polizze.

---

## Minuto 0:30–1:45 — Flusso 1: Benvenuto automatico

**Cosa dire:** *"Chi ti scrive per la prima volta riceve subito una risposta, anche se sei chiuso o impegnato."*

**Cosa fare (dal vivo):**
1. Dal telefono, invia un messaggio qualsiasi ("Ciao") al numero demo.
2. **Mostra il telefono:** in pochi secondi arriva il menu interattivo di benvenuto con i bottoni
   (es. *Info Servizi · Prenota · Parla con noi*).
3. Tocca un bottone → arriva la risposta automatica configurata.

**Il punto da far passare:** *"Nessun cliente resta senza risposta. E l'ho scritto io una volta sola,
in italiano, dalla dashboard."* — mostra **brevemente** `/automations` → card **Benvenuto** per far
vedere che i testi e i bottoni sono personalizzabili, poi torna al telefono.

---

## Minuto 1:45–3:15 — Flusso 2: Promemoria & Scadenze

**Cosa dire:** *"Questo è il flusso che ripaga l'abbonamento da solo: i mancati appuntamenti sono soldi persi."*

**Cosa fare:**
1. Vai su `/appointments` → mostra l'appuntamento creato per tra ~2 ore.
2. Spiega: *"24 ore prima e 2 ore prima, il cliente riceve un promemoria con due bottoni: Confermo / Disdico."*
3. **Mostra sul telefono** un promemoria già inviato (o forzane uno se hai preparato l'appuntamento vicino):
   il cliente tocca **Confermo** → torna su `/appointments` e mostra lo **stato aggiornato** a "Confermato".
4. Aggancia le **scadenze**: *"Lo stesso motore ricorda le scadenze ricorrenti — IMU, 730, rinnovi —
   ai tuoi clienti, in automatico."* → mostra `/scadenze` con il calendario nazionale + le scadenze proprie.

**Il punto da far passare:** *"Meno no-show, zero telefonate di promemoria fatte a mano, e i clienti
confermano con un tap."*

---

## Minuto 3:15–4:15 — Flusso 3: Campagne

**Cosa dire:** *"Quando vuoi raggiungere tutti i clienti in una volta — una promozione, un avviso,
un cambio orari — lo fai da qui, non copiando e incollando su cento chat."*

**Cosa fare:**
1. Vai su `/campaigns` → mostra il compositore: scegli il template, la lingua, vedi il **conteggio
   dei destinatari** (solo contatti che hanno dato consenso — opt-in).
2. Spiega che l'invio è tracciato: stato, inviati, falliti, storico.
3. **Sottolinea il GDPR:** *"Inviamo solo a chi ha acconsentito. È tutto a norma, sul canale ufficiale
   di Meta — niente rischio ban come con le app non ufficiali."*

**Bonus (solo se il cliente è del settore giusto e c'è tempo):** l'add-on **Recensioni Google** —
*"Dopo un appuntamento completato, Replisa chiede automaticamente una recensione. Più recensioni,
più clienti nuovi."* → mostra la card add-on in `/automations`.

---

## Minuto 4:15–4:45 — La dashboard (il controllo)

**Cosa dire:** *"E tu vedi tutto da un posto solo."*

**Cosa fare:** apri `/dashboard`:
- Le card: messaggi inviati/ricevuti, contatti, conversazioni attive, appuntamenti in arrivo.
- Il **grafico** andamento messaggi degli ultimi 14 giorni.
- Il **costo Meta stimato** del mese: *"Sai sempre quanto stai spendendo, senza sorprese."*
- (Se rilevante) `/contacts` → clicca un contatto → **la conversazione completa**, come una chat.

---

## Minuto 4:45–5:00 — Chiusura (la call-to-action)

> *"Questo gira già, oggi, sul canale ufficiale WhatsApp. Possiamo attivarti una **prova gratuita di
> 14 giorni**, senza carta di credito. Ti configuro io il numero e i primi messaggi. Partiamo?"*

**Opzioni di chiusura, in ordine di preferenza:**
1. **Prova subito:** apri la landing → `Inizia ora` → registrazione insieme a lui.
2. **Fissa il setup:** *"Ti mando il link, e domani ci sentiamo 15 minuti per collegare il tuo numero."*
3. **Lascia il gancio:** modulo demo sulla landing (`#demo`) → gli arriva il follow-up.

**Prezzi, solo se chiesto:** Starter €19, Base €49 (il più scelto), Pro €99, Business €199 al mese.
Prova 14 giorni gratis, annuale −20%. *"I costi di conversazione di Meta sono a parte, ma li vedi
stimati in dashboard."*

---

## Se qualcosa va storto (piano B)

| Problema | Piano B |
|---|---|
| Il messaggio WhatsApp non arriva subito | Continua a parlare; spesso arriva con qualche secondo di ritardo. Se no, mostra uno **screenshot preparato** dei messaggi ricevuti. |
| Il reminder non parte (template non approvato) | Mostra il flusso già avvenuto su `/messages` (log) invece che dal vivo. |
| Connessione lenta / niente rete | Usa gli **screenshot** della sequenza (preparali prima!) e racconta come se fosse live. |
| Domanda tecnica a cui non sai rispondere | *"Ottima domanda, me lo segno e ti rispondo entro oggi"* — non improvvisare. |

---

## Cosa NON fare

- Non mostrare i menu di configurazione più di 5 secondi ciascuno: il cliente vuole vedere **cosa fa**, non **come si imposta**.
- Non parlare di prezzi prima di aver mostrato il valore (minuto 4+).
- Non promettere funzioni non ancora disponibili (es. integrazione col suo gestionale specifico) senza verificarle.
- Non superare i 5 minuti nella parte guidata: lascia spazio alle domande.
