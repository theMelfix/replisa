# 🟢 Notifly — Project Backlog & Sprint Plan

> **Prodotto:** Notifly — Automazione WhatsApp per PMI via Meta Cloud API
> **Stack:** Laravel (PHP) · MySQL · VPS personale · Meta Cloud API v18+
> **Brand:** theMelfix / giovannimelfi.com
> **Pricing:** Starter €14 | Base €39 | Pro €79 | Business €149 /mese
> **PM / Scrum Master:** Claude (AI) · **Dev / Product Owner:** Giovanni Melfi
> **Data inizio progetto:** ___/___/2026
> **Ultimo aggiornamento:** 04/05/2026

---

## Legenda stato task

| Emoji | Stato |
|-------|-------|
| ⬜ | To Do |
| 🔵 | In Progress |
| 🟡 | In Review / Testing |
| ✅ | Done |
| 🚫 | Blocked |
| ⏸️ | On Hold |

## Legenda priorità

| Tag | Significato |
|-----|-------------|
| `P0` | Critico — blocca tutto il resto |
| `P1` | Alta — necessario per l'MVP |
| `P2` | Media — migliora il prodotto |
| `P3` | Bassa — nice-to-have, post-lancio |

## Legenda effort (Story Points — scala Fibonacci)

| SP | Significato |
|----|-------------|
| 1 | Poche ore, semplice |
| 2 | Mezza giornata |
| 3 | ~1 giorno |
| 5 | 2-3 giorni |
| 8 | ~1 settimana |
| 13 | >1 settimana, valuta split |

---

## 📋 Product Backlog Overview

Il progetto è suddiviso in **6 Epic** che coprono l'intero ciclo di vita dalla configurazione iniziale al primo cliente pagante.

| # | Epic | Sprint Target | Story Points |
|---|------|---------------|:------------:|
| E1 | Setup Infrastruttura & Meta Cloud API | Sprint 1 | 18 |
| E2 | Core Engine — Messaging & Webhook | Sprint 1-2 | 24 |
| E3 | Flussi Automazione (3 flussi MVP) | Sprint 2-3 | 21 |
| E4 | Dashboard Admin & Multi-Tenant | Sprint 3-4 | 26 |
| E5 | Landing Page & Sales Material | Sprint 2 (parallelo) | 11 |
| E6 | Go-to-Market & Primo Cliente | Sprint 4-5 | 10 |
| | **TOTALE** | | **110** |

---

## 🏃 Sprint Plan

> Velocity stimata: ~20-25 SP/sprint (1 dev, sprint da 2 settimane)
> Durata totale stimata: **10-12 settimane** fino al primo cliente pagante

### Sprint 1 — Fondamenta (Settimane 1-2)

**Goal:** Avere l'ambiente Meta funzionante, poter inviare/ricevere un messaggio WhatsApp via API dal proprio VPS.

### Sprint 2 — Motore & Comunicazione (Settimane 3-4)

**Goal:** Engine di messaging funzionante con template, webhook attivi. Landing page online in parallelo.

### Sprint 3 — Flussi & Dashboard (Settimane 5-6)

**Goal:** I 3 flussi demo operativi. Inizio dashboard di gestione.

### Sprint 4 — Multi-Tenant & Onboarding (Settimane 7-8)

**Goal:** Un cliente può essere onboardato, avere il suo account, e i suoi flussi girano autonomamente.

### Sprint 5 — GTM & Primo Cliente (Settimane 9-10)

**Goal:** Outreach attivo, demo live funzionante, primo cliente pagante.

---

## E1 — Setup Infrastruttura & Meta Cloud API

> **Obiettivo:** Ambiente di sviluppo e Meta Cloud API configurati e funzionanti.

### E1.1 — Setup Progetto Laravel

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 1.1.1 | Creare progetto Laravel (ultima versione stabile) | `P0` | 1 | ⬜ | `laravel new notifly` |
| 1.1.2 | Configurare Git repo + `.gitignore` + primo commit | `P0` | 1 | ⬜ | GitHub/GitLab privato |
| 1.1.3 | Setup `.env` con variabili Meta API (token, phone_id, app_secret) | `P0` | 1 | ⬜ | Mai committare `.env` |
| 1.1.4 | Configurare database MySQL sul VPS | `P0` | 2 | ⬜ | Charset `utf8mb4_unicode_ci` |
| 1.1.5 | Setup dominio/sottodominio per API (es. `api.notifly.it` o `app.notifly.it`) | `P1` | 2 | ⬜ | Registrare notifly.it + certificato SSL obbligatorio per webhook |
| 1.1.6 | Configurare deploy pipeline (Git pull + composer + migrate su VPS) | `P2` | 2 | ⬜ | Anche uno script bash semplice va bene |

### E1.2 — Configurazione Meta Cloud API

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 1.2.1 | Creare Meta Business App su developers.facebook.com | `P0` | 1 | ⬜ | Serve account Business verificato |
| 1.2.2 | Attivare prodotto "WhatsApp" nella dashboard Meta | `P0` | 1 | ⬜ | |
| 1.2.3 | Ottenere numero test sandbox + token temporaneo | `P0` | 1 | ⬜ | Token scade ogni 24h in sandbox |
| 1.2.4 | Generare System User Token (permanente) | `P0` | 2 | ⬜ | Business Settings → System Users |
| 1.2.5 | Testare primo invio messaggio via cURL/Postman | `P0` | 1 | ⬜ | Validazione end-to-end |
| 1.2.6 | Documentare tutti gli ID e i token in modo sicuro | `P1` | 1 | ⬜ | Password manager, non file di testo |

---

## E2 — Core Engine: Messaging & Webhook

> **Obiettivo:** Poter inviare messaggi template, ricevere risposte via webhook, e tracciare tutto su DB.

### E2.1 — Servizio Invio Messaggi

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.1.1 | Creare `WhatsAppService` (classe PHP per wrappare Meta API) | `P0` | 3 | ⬜ | Metodi: `sendTemplate()`, `sendText()`, `sendInteractive()` |
| 2.1.2 | Implementare `sendTemplate()` con supporto parametri dinamici | `P0` | 3 | ⬜ | Componenti header/body/button |
| 2.1.3 | Implementare `sendText()` per messaggi semplici | `P1` | 1 | ⬜ | Solo dentro finestra 24h |
| 2.1.4 | Implementare `sendInteractive()` (bottoni + liste) | `P1` | 3 | ⬜ | Max 3 bottoni, max 10 righe lista |
| 2.1.5 | Gestione errori API Meta (rate limit, token scaduto, numero non valido) | `P0` | 2 | ⬜ | Retry con backoff esponenziale |
| 2.1.6 | Logging di ogni messaggio inviato su tabella `messages` | `P0` | 2 | ⬜ | Campi: to, template, status, meta_id, sent_at |

### E2.2 — Webhook Ricezione Messaggi

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.2.1 | Creare endpoint `GET /webhook` per verifica Meta (challenge) | `P0` | 1 | ⬜ | Risponde con `hub.challenge` |
| 2.2.2 | Creare endpoint `POST /webhook` per ricezione messaggi | `P0` | 3 | ⬜ | Parsing payload JSON Meta |
| 2.2.3 | Validare firma webhook (`X-Hub-Signature-256`) | `P0` | 2 | ⬜ | Sicurezza: senza questo chiunque può inviare fake |
| 2.2.4 | Parsing dei diversi tipi di messaggio (text, interactive reply, button reply) | `P1` | 2 | ⬜ | |
| 2.2.5 | Gestione status updates (sent, delivered, read, failed) | `P1` | 2 | ⬜ | Aggiornare tabella `messages` |
| 2.2.6 | Queue processing: webhook salva su coda, job processa async | `P1` | 3 | ⬜ | Laravel Queue + database driver |

### E2.3 — Database Schema Base

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.3.1 | Migration tabella `tenants` (aziende clienti) | `P0` | 1 | ⬜ | name, phone_number_id, waba_id, plan, active |
| 2.3.2 | Migration tabella `contacts` (contatti dei clienti) | `P0` | 1 | ⬜ | tenant_id, phone, name, opted_in, last_seen |
| 2.3.3 | Migration tabella `messages` (log messaggi) | `P0` | 1 | ⬜ | tenant_id, contact_id, direction, type, content, status, meta_message_id |
| 2.3.4 | Migration tabella `conversations` (sessioni 24h) | `P1` | 1 | ⬜ | tenant_id, contact_id, opened_at, category, billable |
| 2.3.5 | Migration tabella `automations` (flussi configurati) | `P1` | 1 | ⬜ | tenant_id, type, trigger, config_json, active |
| 2.3.6 | Migration tabella `appointments` (appuntamenti per reminder) | `P1` | 1 | ⬜ | tenant_id, contact_id, datetime, status, reminded |

---

## E3 — Flussi di Automazione (MVP)

> **Obiettivo:** I 3 flussi demo funzionanti, mostrabili in una demo live di 5 minuti.

### E3.1 — Welcome Flow

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.1.1 | Creare template Meta "welcome_message" (con bottoni interattivi) | `P0` | 2 | ⬜ | Sottomettere per approvazione Meta (24-48h) |
| 3.1.2 | Implementare trigger: nuovo messaggio in ingresso → check se primo contatto | `P0` | 3 | ⬜ | Controllare se `contact` esiste già |
| 3.1.3 | Risposta automatica con messaggio interattivo (menu servizi) | `P0` | 3 | ⬜ | Bottoni: "Info Servizi", "Prenota", "Parla con noi" |
| 3.1.4 | Gestione risposte ai bottoni (routing verso azione corretta) | `P1` | 3 | ⬜ | Switch su `button_reply.id` |
| 3.1.5 | Opt-in tracking: salvare consenso esplicito del contatto | `P0` | 1 | ⬜ | GDPR: obbligatorio prima di inviare marketing |

### E3.2 — Reminder Appuntamento

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.2.1 | Creare template Meta "appointment_reminder" con parametri (nome, data, ora) | `P0` | 2 | ⬜ | Categoria: UTILITY (costo inferiore) |
| 3.2.2 | Laravel Scheduler: job che gira ogni ora, trova appuntamenti a -24h e -2h | `P0` | 3 | ⬜ | `php artisan schedule:run` via cron |
| 3.2.3 | Invio reminder con bottoni "✅ Confermo" / "❌ Disdici" | `P0` | 2 | ⬜ | |
| 3.2.4 | Gestione risposta: aggiornare stato appuntamento su DB | `P1` | 2 | ⬜ | Notificare l'attività in caso di disdetta |
| 3.2.5 | Endpoint API per inserimento appuntamenti (da gestionale esterno) | `P1` | 2 | ⬜ | `POST /api/v1/appointments` autenticato |

### E3.3 — Richiesta Recensione

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.3.1 | Creare template Meta "review_request" con link Google Reviews | `P0` | 2 | ⬜ | Categoria: MARKETING (serve opt-in) |
| 3.3.2 | Job schedulato: invia richiesta 24h dopo visita/appuntamento completato | `P0` | 2 | ⬜ | Solo se appuntamento status = "completed" |
| 3.3.3 | Gestione risposta: tracking chi ha cliccato / risposto | `P2` | 1 | ⬜ | Utile per analytics |
| 3.3.4 | Rate limiting: non inviare più di 1 richiesta recensione per contatto ogni 30 giorni | `P1` | 1 | ⬜ | Evitare spam |

---

## E4 — Dashboard Admin & Multi-Tenant

> **Obiettivo:** Un pannello web dove ogni cliente gestisce i propri flussi, vede le statistiche, e opera autonomamente.

### E4.1 — Autenticazione & Multi-Tenancy

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.1.1 | Setup Laravel Breeze/Jetstream per autenticazione | `P0` | 2 | ⬜ | Login, registrazione, reset password |
| 4.1.2 | Middleware tenant: ogni utente vede solo i dati del suo tenant | `P0` | 3 | ⬜ | Scope globale su tutte le query |
| 4.1.3 | Ruoli base: admin (tu), owner (cliente), operator (dipendente) | `P1` | 2 | ⬜ | Spatie/Laravel-Permission |
| 4.1.4 | Super-admin area: gestire tutti i tenant, attivare/disattivare account | `P1` | 3 | ⬜ | Solo per te |

### E4.2 — Dashboard Cliente

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.2.1 | Overview: messaggi inviati/ricevuti, conversazioni attive, costi stimati | `P1` | 3 | ⬜ | Widget con grafici base |
| 4.2.2 | Sezione Contatti: lista, ricerca, dettaglio conversazione | `P1` | 3 | ⬜ | |
| 4.2.3 | Sezione Automazioni: attiva/disattiva flussi, configura parametri | `P1` | 3 | ⬜ | Toggle on/off per flusso |
| 4.2.4 | Sezione Appuntamenti: CRUD manuale + import CSV | `P2` | 3 | ⬜ | Per chi non ha gestionale |
| 4.2.5 | Log messaggi: cronologia completa con status (sent/delivered/read/failed) | `P1` | 2 | ⬜ | Filtri per data, contatto, tipo |
| 4.2.6 | Sezione Billing: piano attivo, conteggio messaggi, upgrade | `P2` | 3 | ⬜ | Per MVP basta mostrare il piano |

### E4.3 — API Pubblica per Integrazioni

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.3.1 | Autenticazione API con API Key per tenant | `P1` | 2 | ⬜ | Laravel Sanctum |
| 4.3.2 | `POST /api/v1/messages/send` — invio messaggio singolo | `P1` | 2 | ⬜ | Per integrazioni gestionali |
| 4.3.3 | `POST /api/v1/appointments` — creare appuntamento | `P1` | 1 | ⬜ | Trigger reminder automatico |
| 4.3.4 | `GET /api/v1/messages` — lista messaggi con paginazione | `P2` | 1 | ⬜ | |
| 4.3.5 | Documentazione API (Swagger/OpenAPI) | `P2` | 2 | ⬜ | Utile per vendere a PMI con dev |

---

## E5 — Landing Page & Sales Material

> **Obiettivo:** Materiale commerciale pronto per vendere. Eseguibile in parallelo agli sprint tecnici.

### E5.1 — Landing Page

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 5.1.1 | Pagina su notifly.it (dominio dedicato) | `P0` | 3 | ⬜ | Hero + problema/soluzione + pricing + CTA |
| 5.1.2 | Form contatto / Calendly embed per prenotare chiamata | `P0` | 1 | ⬜ | |
| 5.1.3 | SEO base: meta tags, Open Graph, structured data | `P1` | 1 | ⬜ | |
| 5.1.4 | Cookie banner GDPR | `P1` | 1 | ⬜ | Hai già esperienza da giovannimelfi.com |

### E5.2 — Materiale Commerciale

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 5.2.1 | Brochure PDF aggiornata (4 piani tariffari, costi Meta inclusi) | `P1` | 2 | ⬜ | Versione precedente già creata, da aggiornare |
| 5.2.2 | Template messaggio outreach (email + DM Instagram) | `P1` | 1 | ⬜ | |
| 5.2.3 | Script demo live 5 minuti (cosa mostrare, in che ordine) | `P1` | 1 | ⬜ | Welcome → Reminder → Recensione |
| 5.2.4 | Video demo registrato (screen recording con voice-over) | `P3` | 1 | ⬜ | Post-MVP, quando i flussi girano |

---

## E6 — Go-to-Market & Primo Cliente

> **Obiettivo:** Primo cliente pagante entro 10-12 settimane dal kickoff.

### E6.1 — Outreach

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 6.1.1 | Lista 20-30 PMI target nella tua zona (Vittoria + provincia) | `P0` | 2 | ⬜ | Ristoranti, studi medici, palestre, assicurazioni |
| 6.1.2 | Invio primo batch di outreach (10 contatti) | `P0` | 1 | ⬜ | |
| 6.1.3 | Follow-up dopo 3-5 giorni sui non-rispondenti | `P1` | 1 | ⬜ | |
| 6.1.4 | Demo live al primo interessato | `P0` | 1 | ⬜ | Usare lo script E5.2.3 |

### E6.2 — Onboarding Primo Cliente

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 6.2.1 | Processo onboarding documentato (checklist step-by-step) | `P1` | 2 | ⬜ | Registrazione WABA, verifica numero, setup template |
| 6.2.2 | Contratto di servizio / T&C base | `P1` | 2 | ⬜ | Consulta commercialista per P.IVA |
| 6.2.3 | Setup account cliente sulla piattaforma | `P0` | 1 | ⬜ | |
| 6.2.4 | Personalizzazione template messaggi per il cliente | `P1` | 1 | ⬜ | |
| 6.2.5 | Raccolta feedback post-onboarding | `P2` | 1 | ⬜ | Base per testimonial |

---

## 🧊 Icebox (Post-MVP)

Task importanti ma non necessari per il lancio. Da rivalutare dopo il primo cliente.

| # | Task | Epic | Note |
|---|------|------|------|
| ICE-1 | Chatbot builder drag & drop | E3 | Flussi personalizzati senza codice |
| ICE-2 | Integrazione Stripe/Fattura24 per billing automatico | E4 | Quando hai 5+ clienti |
| ICE-3 | Integrazione con gestionali specifici (Xdent, Fatturecloud, ecc.) | E4 | Connettori per verticali |
| ICE-4 | Analytics avanzati (conversion rate, response time, engagement) | E4 | Dashboard dati avanzata |
| ICE-5 | WhatsApp Flows (form nativi dentro WhatsApp) | E3 | Feature Meta relativamente nuova |
| ICE-6 | Multi-numero: gestire più numeri WhatsApp per tenant | E2 | Per clienti con più sedi |
| ICE-7 | Broadcast / campagne marketing schedulabili | E3 | Richiede gestione opt-in rigorosa |
| ICE-8 | Template gallery: libreria di template pre-approvati per settore | E3 | Riduce tempo onboarding |
| ICE-9 | Notifiche via email all'owner quando un cliente disdice | E3 | Alert in tempo reale |
| ICE-10 | App mobile nativa per il cliente (React Native) | E4 | Long-term, quando il prodotto è maturo |

---

## 📝 Definition of Done (DoD)

Un task è "Done" quando:

1. Il codice è scritto, testato e committato
2. Le migration sono state eseguite senza errori
3. I test (almeno manuali) confermano il funzionamento
4. Il codice è stato deployato sul VPS (ambiente staging o produzione)
5. La documentazione interna è aggiornata (commenti, README)

---

## 🔄 Cerimonie Scrum

| Cerimonia | Frequenza | Durata | Cosa |
|-----------|-----------|--------|------|
| Sprint Planning | Ogni 2 settimane (lunedì) | 30 min | Selezionare task dallo sprint target |
| Daily Standup | Quotidiano (asincrono) | 5 min | Cosa ho fatto, cosa farò, blockers |
| Sprint Review | Fine sprint (venerdì) | 20 min | Demo di ciò che funziona |
| Sprint Retro | Fine sprint | 15 min | Cosa ha funzionato, cosa migliorare |

> **Nota pratica:** Essendo un progetto one-man, le cerimonie sono semplificate. Il "daily standup" può essere un appunto rapido nel file. La Sprint Review è una sessione con Claude per fare il punto e pianificare il prossimo sprint.

---

## 📊 Sprint Tracking

### Sprint 1 — Fondamenta

| Metrica | Valore |
|---------|--------|
| **Sprint Goal** | Ambiente Meta funzionante, invio/ricezione primo messaggio |
| **SP Pianificati** | — |
| **SP Completati** | — |
| **Data Inizio** | ___/___/2026 |
| **Data Fine** | ___/___/2026 |
| **Velocity** | — |
| **Blockers** | — |
| **Note Retro** | — |

---

*Questo file è il single source of truth del progetto. Aggiornalo ad ogni task completato.*
*Per assistenza su qualsiasi task: apri una conversazione con Claude e indica il codice task (es. "Aiutami con il task 2.2.3").*
