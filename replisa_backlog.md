# 🟢 Replisa — Project Backlog & Sprint Plan

> **Prodotto:** Replisa — Automazione WhatsApp per PMI via Meta Cloud API
> *(progetto noto in fase preliminare come "Notifly", rinominato il 2026-05-15 — vedi ADR-004)*
> **Stack:** Laravel (PHP) · MySQL · VPS personale · Meta Cloud API v25.0+
> **Domini:** `replisa.com` (primario) · `replisa.it` (difensivo, 301→.com)
> **Brand:** theMelfix / giovannimelfi.com
> **Pricing:** Starter €14 | Base €39 | Pro €79 | Business €149 /mese
> **PM / Scrum Master:** Claude (AI) · **Dev / Product Owner:** Giovanni Melfi
> **Data inizio progetto:** 14/05/2026
> **Ultimo aggiornamento:** 19/08/2026 — backoffice con sidebar, area admin (scheda cliente + Panoramica) e marchio nuovo (su `develop`)

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
| 1.1.1 | Creare progetto Laravel (ultima versione stabile) | `P0` | 1 | ✅ | Fatto 2026-05-14, commit `ea35082` |
| 1.1.2 | Configurare Git repo + `.gitignore` + primo commit | `P0` | 1 | ✅ | Repo `theMelfix/notifly` privato (da rinominare → `replisa`), branch main/develop |
| 1.1.3 | Setup `.env` con variabili Meta API (token, phone_id, app_secret) | `P0` | 1 | ✅ | Placeholder pronti in `.env.example` |
| 1.1.4 | Configurare database MySQL sul VPS | `P0` | 2 | ✅ | MariaDB su VPS IONOS. DB `replisa` (utf8mb4) + user `replisa_app` (localhost only) creati via CloudPanel UI 2026-05-15. Credenziali in Bitwarden |
| 1.1.5 | Setup dominio/sottodominio per API (es. `api.replisa.com`) | `P1` | 2 | ✅ | DNS A per `@`/`www`/`app`/`api` → VPS IONOS. SSL Let's Encrypt attivo via CloudPanel su `replisa.com` + `www`. `replisa.it` con redirect 301 → `.com` (SSL incluso). 2026-05-15 |
| 1.1.6 | Configurare deploy pipeline (Git pull + composer + migrate su VPS) | `P2` | 2 | ✅ | DPLOY (CloudPanel) con release atomiche stile Capistrano, `.env` in overlays, sudoers per reload php8.4-fpm. Primo deploy 2026-05-16: https://replisa.com live con Laravel 13. Trigger ad oggi manuale (`dploy deploy main`); automazione GH Actions tracciata come task post-Sprint 1 |

### E1.2 — Configurazione Meta Cloud API

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 1.2.1 | Creare Meta Business App su developers.facebook.com | `P0` | 1 | ✅ | App creata 2026-05-15 con working name "Notifly" — da rinominare in "Replisa" via dashboard Meta |
| 1.2.2 | Attivare prodotto "WhatsApp" nella dashboard Meta | `P0` | 1 | ✅ | WABA Test + numero sandbox attivi |
| 1.2.3 | Ottenere numero test sandbox + token temporaneo | `P0` | 1 | ✅ | PHONE_NUMBER_ID e WABA_ID raccolti |
| 1.2.4 | Generare System User Token (permanente) | `P0` | 2 | ✅ | Token System User generato (scope `whatsapp_business_messaging` + `_management`), salvato in `.env` come SANDBOX_ACCESS_TOKEN |
| 1.2.5 | Testare primo invio messaggio via cURL/Postman | `P0` | 1 | ✅ | Template `hello_world` consegnato. Script in `scripts/whatsapp-test-send.sh` |
| 1.2.6 | Documentare tutti gli ID e i token in modo sicuro | `P1` | 1 | ✅ | Bitwarden cloud (free) attivato. Credenziali Meta (App, WABA, System User, Webhook verify) salvate. Migrazione futura a Vaultwarden self-hosted prevista (no lock-in) |

---

## E2 — Core Engine: Messaging & Webhook

> **Obiettivo:** Poter inviare messaggi template, ricevere risposte via webhook, e tracciare tutto su DB.

### E2.1 — Servizio Invio Messaggi

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.1.1 | Creare `WhatsAppService` (classe PHP per wrappare Meta API) | `P0` | 3 | ✅ | `App\Services\WhatsApp\WhatsAppService`, scoped per-tenant (`::for($tenant)`). Config Meta in `config/services.php`. 2026-06-08 |
| 2.1.2 | Implementare `sendTemplate()` con supporto parametri dinamici | `P0` | 3 | ✅ | Lingua + array `components` (header/body/button) passati raw. 2026-06-08 |
| 2.1.3 | Implementare `sendText()` per messaggi semplici | `P1` | 1 | ✅ | `sendText($to,$body,$previewUrl)`. Finestra 24h applicata da Meta (err 131047 → eccezione). 2026-06-08 |
| 2.1.4 | Implementare `sendInteractive()` (bottoni + liste) | `P1` | 3 | ✅ | `sendButtons()` (1-3 reply button) + `sendList()` (1-10 righe), con validazione limiti. 2026-06-08 |
| 2.1.5 | Gestione errori API Meta (rate limit, token scaduto, numero non valido) | `P0` | 2 | ✅ | `WhatsAppApiException` con `metaCode`/`httpStatus`. Retry+backoff (250/500ms) **solo** su transitori (429/5xx/connessione). 2026-06-08 |
| 2.1.6 | Logging di ogni messaggio inviato su tabella `messages` | `P0` | 2 | ✅ | Riga `queued`→`sent`(+`meta_message_id`) o `failed`(+`error` json). Contatto risolto via `firstOrCreate`. 2026-06-08 |

### E2.2 — Webhook Ricezione Messaggi

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.2.1 | Creare endpoint `GET /webhook` per verifica Meta (challenge) | `P0` | 1 | ✅ | `WebhookController@verify`, risponde `hub.challenge` in text/plain con `hash_equals` sul verify token. 2026-06-09 |
| 2.2.2 | Creare endpoint `POST /webhook` per ricezione messaggi | `P0` | 3 | ✅ | `WebhookController@handle`. Route in `routes/webhook.php` fuori dal gruppo `web` (no CSRF). 2026-06-09 |
| 2.2.3 | Validare firma webhook (`X-Hub-Signature-256`) | `P0` | 2 | ✅ | Middleware `meta.signature` (HMAC-SHA256 del body raw con app_secret, fail-closed). 2026-06-09 |
| 2.2.4 | Parsing dei diversi tipi di messaggio (text, interactive reply, button reply) | `P1` | 2 | ✅ | `extractContent()`: text/interactive (button_reply+list_reply)/button quick-reply. 2026-06-09 |
| 2.2.5 | Gestione status updates (sent, delivered, read, failed) | `P1` | 2 | ✅ | Match per `meta_message_id`, aggiorna `status` (+`error` su failed). 2026-06-09 |
| 2.2.6 | Queue processing: webhook salva su coda, job processa async | `P1` | 3 | ✅ | `ProcessWhatsAppWebhook` (ShouldQueue, idempotente su `meta_message_id`). Endpoint risponde 200 subito. ⚠️ Serve queue worker in prod (vedi nota infra). 2026-06-09 |

### E2.3 — Database Schema Base

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 2.3.1 | Migration tabella `tenants` (aziende clienti) | `P0` | 1 | ✅ | name, phone_number_id (unique), waba_id, access_token (cast `encrypted` — ADR-003), plan, active. Model `Tenant` con relazioni. 2026-06-08 |
| 2.3.2 | Migration tabella `contacts` (contatti dei clienti) | `P0` | 1 | ✅ | tenant_id (FK cascade), phone, name, opted_in + opted_in_at, last_seen_at. Unique `(tenant_id, phone)`. Model `Contact`. 2026-06-08 |
| 2.3.3 | Migration tabella `messages` (log messaggi) | `P0` | 1 | ✅ | tenant_id + contact_id (FK cascade), direction, type, content (json), status, meta_message_id (index), error (json). Model `Message`. 2026-06-08 |
| 2.3.4 | Migration tabella `conversations` (sessioni 24h) | `P1` | 1 | ✅ | FK tenant+contact (cascade), `category`, `billable`, `opened_at`, `expires_at`. Model `Conversation`. 2026-06-09 |
| 2.3.5 | Migration tabella `automations` (flussi configurati) | `P1` | 1 | ✅ | FK tenant (cascade), `type`, `trigger`, `config` (json), `active`. Unique `(tenant_id, type)`. Costanti `TYPE_*`. 2026-06-09 |
| 2.3.6 | Migration tabella `appointments` (appuntamenti per reminder) | `P1` | 1 | ✅ | FK tenant+contact (cascade), `scheduled_at`, `status`, `reminded_at`, `review_requested`. Costanti `STATUS_*`. 2026-06-09 |

---

## E3 — Flussi di Automazione (MVP)

> **Obiettivo:** I 3 flussi demo funzionanti, mostrabili in una demo live di 5 minuti.

### E3.1 — Welcome Flow

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.1.1 | Creare template Meta "welcome_message" (con bottoni interattivi) | `P0` | 2 | ⬜ | Azione su dashboard Meta (approvazione 24-48h). **Non blocca il flusso**: il menu welcome viaggia come messaggio interattivo free-form dentro la finestra 24h aperta dall'inbound — il template servirebbe solo per ri-aprire la conversazione fuori finestra |
| 3.1.2 | Implementare trigger: nuovo messaggio in ingresso → check se primo contatto | `P0` | 3 | ✅ | `WelcomeFlow` agganciato a `ProcessWhatsAppWebhook`: trigger su `$contact->wasRecentlyCreated`, solo su messaggi appena loggati (idempotente). 2026-06-09, commit `2e859a0` |
| 3.1.3 | Risposta automatica con messaggio interattivo (menu servizi) | `P0` | 3 | ✅ | `WelcomeFlow::greet()` invia `sendButtons` (default: Info Servizi / Prenota / Parla con noi). Testo+bottoni personalizzabili per-tenant via `automations.config`. 2026-06-09 |
| 3.1.4 | Gestione risposte ai bottoni (routing verso azione corretta) | `P1` | 3 | ✅ | `handleButtonReply()`: id namespaced `welcome:<action>`, risposta da `config.replies[action]`. 2026-06-09 |
| 3.1.5 | Opt-in tracking: salvare consenso esplicito del contatto | `P0` | 1 | ✅ | `recordOptIn()` al primo contatto (`opted_in`+`opted_in_at`, idempotente). Marketing richiederà opt-in dedicato. 2026-06-09 |

### E3.2 — Promemoria & Scadenze (ex Reminder Appuntamento)

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.2.1 | Creare template Meta "appointment_reminder" con parametri (nome, data, ora) | `P0` | 2 | 🟡 | **Specifica pronta** in `docs/META-TEMPLATES.md §1` (UTILITY, `it`, body {{1}}nome/{{2}}data/{{3}}ora, 2 quick-reply). Resta l'azione manuale su WhatsApp Manager (per-WABA, ADR-003) + action item: allineare payload bottoni (`CONFIRM`/`CANCEL`) ai testi italiani. 2026-06-29 |
| 3.2.2 | Laravel Scheduler: job che gira ogni ora, trova appuntamenti a -24h e -2h | `P0` | 3 | ✅ | Command `replisa:send-reminders` + `AppointmentReminder::dispatchDue()`. Logica due idempotente a due finestre sull'unica colonna `reminded_at`. Schedule `->hourly()` in `routes/console.php`. ⚠️ Serve cron `schedule:run` sul VPS (vedi deploy/README). 2026-06-10, commit `e45e73b` |
| 3.2.3 | Invio reminder con bottoni "✅ Confermo" / "❌ Disdici" | `P0` | 2 | ✅ | `remind()` invia il template (i bottoni quick-reply fanno parte del template 3.2.1) e segna `reminded_at`. Fallimenti loggati senza marcare reminded_at → retry. 2026-06-10 |
| 3.2.4 | Gestione risposta: aggiornare stato appuntamento su DB | `P1` | 2 | ✅ | `handleButtonReply()` su messaggi inbound tipo `button`: `Confermo`→confirmed / `Disdico`→cancelled sul prossimo appuntamento scheduled del contatto. Match case-insensitive + fallback `CONFIRM`/`CANCEL`. Agganciato a `ProcessWhatsAppWebhook`. 2026-06-10; payload allineati alle label template 2026-06-29 |
| 3.2.5 | Endpoint API per inserimento appuntamenti (da gestionale esterno) | `P1` | 2 | ✅ | Realizzato con E4.3.3: `POST /api/v1/appointments` (auth Sanctum). 2026-07-20 |
| 3.2.6 | Generalizzare in "Promemoria & Scadenze": scadenze ricorrenti senza conferma (IMU/730/rinnovi) | `P1` | 3 | ✅ | `Deadline` (nazionali admin + proprie tenant) + `DeadlineReminder` + seeder 2026. Admin `/admin/deadlines`, tenant `/scadenze`, command `replisa:send-deadline-reminders` (avvia campagne agli opted-in). **Settori**: le nazionali sono filtrate per categoria attività (le fiscali solo ai commercialisti); `sector` su tenant/scadenza, richiesto in registrazione. 2026-06-21 |

### E3.3 — Richiesta Recensione (add-on)

> **Nota (2026-06-21/22):** non più flusso core — venduto come **add-on Recensioni Google** (+€19/mese, incluso in Business). Gating fatto (`PlanLimits::hasReviewsAddon()`: Business o `reviews_addon` concesso dall'admin; gate nel command + card add-on in Automazioni). Manca: UI di acquisto self-service (Stripe).

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.3.1 | Creare template Meta "review_request" con link Google Reviews | `P0` | 2 | 🟡 | **Specifica pronta** in `docs/META-TEMPLATES.md §2` (MARKETING, `it`, body {{1}}nome, button URL statico **o** dinamico via `review_url_param`). Scelta statico/dinamico + suffisso ora **configurabili da UI** (`/automations` → card Recensioni, `Automations::saveReviewSettings()`, con validazione anti-incoerenza, 6 test). Resta l'azione manuale su WhatsApp Manager (per-WABA); follow-up: introspezione template via Graph API. 2026-06-29 |
| 3.3.2 | Job schedulato: invia richiesta 24h dopo visita/appuntamento completato | `P0` | 2 | ✅ | `App\Services\Automation\ReviewRequest` + command `replisa:send-review-requests` schedulato `->hourly()`. `dispatchDue()` trova gli appuntamenti `completed` con `review_requested=false` e `scheduled_at <= now-delay` (delay default 24h, configurabile). Solo verso contatti `opted_in`. 2026-06-17 |
| 3.3.3 | Gestione risposta: tracking chi ha cliccato / risposto | `P2` | 1 | ✅ | Short-link tracciato: nuova **modalità "tracciato"** del link recensione (`ReviewClick` + rotta pubblica `/r/{token}`). Il template punta a `{APP_URL}/r/{{1}}`, il codice genera un token per invio, la rotta registra il click e reindirizza all'URL Google. Statistiche (inviate/cliccato/click totali) in `/automations`. 2026-07-22 |
| 3.3.4 | Rate limiting: non inviare più di 1 richiesta recensione per contatto ogni 30 giorni | `P1` | 1 | ✅ | `recentlyRequested()`: nessuna nuova richiesta se un template recensione (non `failed`) è già partito al contatto entro `rate_limit_days` (default 30). Appuntamento saltato comunque marcato `review_requested` per non rivalutarlo. 2026-06-17 |

### E3.4 — Campagne e comunicazioni (nuovo prodotto core)

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 3.4.1 | Prodotto/flusso "Campagne" in UI + landing | `P1` | 1 | ✅ | `Automation::TYPE_CAMPAIGN`, card landing, pagina `/campaigns`. 2026-06-21 |
| 3.4.2 | Engine invio a liste/segmenti di contatti | `P1` | 5 | 🟡 | Modello `Campaign` + job `SendCampaign` (coda): invio template MARKETING a tutti i contatti **opted-in** del tenant, con conteggi/stato e storico. Compositore in `/campaigns`. Manca: segmentazione avanzata (filtri), gating per piano, throttling fine. 2026-06-21 |
| 3.4.3 | Segmentazione contatti (tag/filtri) | `P2` | 3 | ✅ | Etichette per-tenant (`Tag` + pivot `contact_tag`): assegna/rimuovi dal dettaglio contatto, filtro per etichetta nella lista Contatti. Le campagne hanno un **segmento** (`campaigns.tag_id`, null=tutti gli opt-in): conteggio live nel compositore e `Contact::scopeCampaignRecipients()` condiviso tra compositore e job. 2026-07-22 |

---

## E4 — Dashboard Admin & Multi-Tenant

> **Obiettivo:** Un pannello web dove ogni cliente gestisce i propri flussi, vede le statistiche, e opera autonomamente.

### E4.1 — Autenticazione & Multi-Tenancy

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.1.1 | Setup Laravel Breeze/Jetstream per autenticazione | `P0` | 2 | ✅ | Breeze stack **Livewire** (Volt). Registrazione self-service crea Tenant + utente `owner`. Estesa con **dati fiscali** (ragione sociale, P.IVA, CF, indirizzo, SDI/PEC) e **verifica P.IVA** (checksum + VIES, anti-spam): vedi 6.2.3. `users.tenant_id` nullable (super-admin=null). 2026-06-17 (fiscale 2026-06-21) |
| 4.1.2 | Middleware tenant: ogni utente vede solo i dati del suo tenant | `P0` | 3 | ✅ | `TenantScope` global + trait `BelongsToTenant` su Contact/Message/Conversation/Automation/Appointment. No-op in console/webhook (nessun auth) e per super-admin. Auto-fill `tenant_id` in scrittura; backstop `TenantContextException` se un utente autenticato senza tenant tenta una scrittura per-tenant (2026-06-19). 2026-06-17 |
| 4.1.3 | Ruoli base: admin (tu), owner (cliente), operator (dipendente) | `P1` | 2 | ✅ | `spatie/laravel-permission`. Ruoli `super-admin`/`owner`/`operator` (RoleSeeder, costanti su `User`). 2026-06-17 |
| 4.1.4 | Super-admin area: gestire tutti i tenant, attivare/disattivare account | `P1` | 3 | ✅ | Livewire `Admin\Tenants` su `/admin/tenants` (`role:super-admin`). Potenziato (2026-06-21): dati fiscali + P.IVA validata, piano effettivo+fonte, MRR; azioni blocca/sblocca (+ middleware `tenant.active` → `/suspended`), disdici/rimborsa Stripe, **licenze offline** (`manual_plan`, prioritaria in `PlanLimits`). Command `replisa:create-admin`. 2026-06-17 |

### E4.2 — Dashboard Cliente

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.2.1 | Overview: messaggi inviati/ricevuti, conversazioni attive, costi stimati | `P1` | 3 | ✅ | Livewire `Dashboard` con card (inviati/ricevuti/contatti/conversazioni attive/appuntamenti). **Grafico** andamento messaggi ultimi 14gg (barre CSS inviati/ricevuti, dependency-free) + **costo Meta stimato** del mese (template inviati × tariffa `config/whatsapp.php`, con disclaimer). 2026-06-19 / 2026-07-22 |
| 4.2.2 | Sezione Contatti: lista, ricerca, dettaglio conversazione | `P1` | 3 | ✅ | Livewire `Contacts`: lista paginata + ricerca nome/telefono + conteggio messaggi. **Dettaglio conversazione** (2026-07-22): click su una riga apre un pannello laterale con la cronologia messaggi in bolle chat (inbound/outbound, timestamp+stato), ultimi 200, sola lettura. `Message::displayText()` normalizza i tipi di contenuto (riusato anche nel Log). Apertura scoped dal TenantScope. 2026-06-19 / 2026-07-22 |
| 4.2.3 | Sezione Automazioni: attiva/disattiva flussi, configura parametri | `P1` | 3 | ✅ | Livewire `Automations`: toggle on/off dei 3 flussi (crea/aggiorna `Automation` per-tenant). Hardening: backstop `tenant_id` (`TenantContextException`) + sistema toast in-app `<x-toast-hub />` per le eccezioni (niente più 500/`1364`). **Configurazione parametri** (2026-07-20): pannello inline per flusso attivo — Benvenuto (saluto, header/footer, 3 bottoni + risposte, id stabili) e Promemoria (template Meta, lingua, anticipi in ore, label conferma/disdetta); Campagne rimanda alla sua sezione. Scrittura config unificata in `updateConfig()`. 2026-06-19 / 2026-07-20 |
| 4.2.4 | Sezione Appuntamenti: CRUD manuale + import CSV | `P2` | 3 | ✅ | Livewire `Appointments`: form nuovo (crea contatto), cambio stato, elimina, import CSV (telefono,nome,data). Scoped per-tenant. 2026-06-19 |
| 4.2.5 | Log messaggi: cronologia completa con status (sent/delivered/read/failed) | `P1` | 2 | ✅ | Livewire `Messages`: cronologia paginata, badge stato, ricerca contatto + filtri direzione/stato/**tipo**/**intervallo date** (da/a, estremi inclusivi con parsing difensivo) + "Azzera filtri". Tutti i filtri sincronizzati nell'URL. 2026-06-19 / 2026-07-22 |
| 4.2.6 | Sezione Billing: piano attivo, conteggio messaggi, upgrade | `P2` | 3 | ✅ | Stripe via **Laravel Cashier** (Billable su `Tenant`). Pagina `/billing`: 4 piani → Stripe Checkout ospitato + Billing Portal per gestione/disdetta; webhook auto-registrato. `config/plans.php` (Price ID da `.env`). Riquadro "Utilizzo" con conteggio messaggi inviati/ricevuti del mese + contatti vs limite. Enforcement limiti via `App\Support\PlanLimits` (automazioni attive + contatti nuovi su form/CSV; webhook inbound escluso). **Gating per piano** (`allows()`: campagne/scadenze da Base) e **add-on Recensioni** (`hasReviewsAddon()`: Business o concesso). **Fix checkout Livewire** (`Checkout::redirect()`/`redirectToBillingPortal()` davano TypeError in Livewire → uso di `$this->redirect($url)`). **Billing verificato e2e su prod in test mode**: checkout + webhook (`pending_webhooks=0`) + Billing Portal ok. **Passaggio a LIVE fatto (2026-07-29)**: account attivato, 9 Price ID live + chiavi `pk_live`/`sk_live` + endpoint webhook live nell'overlay, verificati sul VPS con il nuovo comando `replisa:stripe-check` (9/9 `ok (LIVE)`) e con un evento reale consegnato `200 OK`. **NB: nessuna IVA** — regime forfettario, i prezzi restano as-is e NON vanno ricreati `exclusive` (l'indicazione precedente era errata); fattura elettronica fuori da Stripe. Resta la **prova d'acquisto reale**, a carico dell'utente offline (tracciata in `docs/GO-LIVE.md`). 2026-06-20/22, 2026-07-28/29 |

### E4.3 — API Pubblica per Integrazioni

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 4.3.1 | Autenticazione API con API Key per tenant | `P1` | 2 | ✅ | Laravel Sanctum, token personali su `User` (l'isolamento resta sul `TenantScope` già testato). UI `ApiTokens` (`/api-tokens`, "API & Integrazioni" nel menu): genera/revoca chiavi, token in chiaro mostrato una sola volta. 2026-07-20 |
| 4.3.2 | `POST /api/v1/messages/send` — invio messaggio singolo | `P1` | 2 | ✅ | `Api\V1\MessageController@send`: `type` text/template. Riusa `WhatsAppService`; contatto risolto nel controller per rispettare il limite contatti del piano (no bypass via API). Errore Meta → 502 con `meta_code`. 2026-07-20 |
| 4.3.3 | `POST /api/v1/appointments` — creare appuntamento | `P1` | 1 | ✅ | `Api\V1\AppointmentController@store`: crea appuntamento (+contatto se nuovo, nei limiti del piano); `scheduled_at` deve essere futuro. Il reminder parte poi dallo scheduler E3.2. Chiude anche il task 3.2.5. 2026-07-20 |
| 4.3.4 | `GET /api/v1/messages` — lista messaggi con paginazione | `P2` | 1 | ✅ | `MessageController@index`: paginato, filtri `direction`/`status`/`per_page` (max 100), isolato dal TenantScope. `MessageResource`. 2026-07-20 |
| 4.3.5 | Documentazione API (Swagger/OpenAPI) | `P2` | 2 | ✅ | Pagina `/api-docs` (guida in italiano con esempi cURL per i 3 endpoint) invece di Swagger — target PMI con un dev. Auth base, URL base dinamico. 2026-07-20 |

---

## E5 — Landing Page & Sales Material

> **Obiettivo:** Materiale commerciale pronto per vendere. Eseguibile in parallelo agli sprint tecnici.

### E5.1 — Landing Page

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 5.1.1 | Pagina su replisa.com (dominio dedicato) | `P0` | 3 | ✅ | Landing v1: hero, 3 automazioni (Benvenuto/Promemoria&Scadenze/Campagne), prezzi **19/49/99/199** + toggle annuale -20% + prova 14gg + add-on Recensioni €19, CTA demo, footer legale. Tailwind/Vite. 2026-06-19 (prezzi rivisti 2026-06-23, vedi `docs/PRICING-REVIEW.md`) |
| 5.1.2 | Form contatto / Calendly embed per prenotare chiamata | `P0` | 1 | ✅ | Livewire `ContactForm` nella sezione `#demo` della landing (sostituisce il CTA `mailto:`). Salva un `Lead` (non tenant-owned) e notifica il team via email on-demand a `services.contact.notify_email`. Honeypot anti-bot. **Vista admin** `/admin/leads` (`Admin\Leads`, `role:super-admin`): lista paginata, ricerca (nome/email/attività), filtro per stato con conteggi, avanzamento stato (nuovo→contattato→convertito/archiviato) ed eliminazione. 2026-07-21 |
| 5.1.3 | SEO base: meta tags, Open Graph, structured data | `P1` | 1 | ✅ | Title/description/OG + canonical, og:url/image/site_name/locale, Twitter card. **JSON-LD** (`@graph`: Organization + WebSite + SoftwareApplication con `AggregateOffer` dei 4 piani, prezzi da `config/plans.php`). 2026-07-21 |
| 5.1.4 | Cookie banner GDPR | `P1` | 1 | ✅ | Componente autonomo `<x-cookie-banner />` (CSS+JS inline, nessuna dipendenza dal framework del layout) su landing/guest/legale/app. Il sito usa **solo cookie tecnici** (esenti da consenso, art. 122) → banner **informativo** con presa visione persistita in localStorage. Privacy §8 ampliata (anchor `#cookie`). Da evolvere in accept/reject granulare se si aggiungono cookie di misurazione. 2026-07-21 |

### E5.2 — Materiale Commerciale

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 5.2.1 | Brochure PDF aggiornata (4 piani tariffari, costi Meta inclusi) | `P1` | 2 | ✅ | `brand/brochure.html`: 2 pagine A4 print-ready (Stampa→PDF), logo brand incorporato (base64, file autonomo). Pag.1 hero + 3 flussi + benefici + nota costi Meta; pag.2 i 4 piani (mensile+annuale) + add-on Recensioni + CTA. Prezzi/feature da `config/plans.php`. 2026-07-22 |
| 5.2.2 | Template messaggio outreach (email + DM Instagram) | `P1` | 1 | ✅ | `docs/OUTREACH-TEMPLATES.md`: email a freddo + 2 follow-up, DM Instagram + follow-up, WhatsApp (con cautela), ganci per segmento (ristorante/studio/palestra/commercialista/assicurazione), principi e tracking lead. Firma Giovanni Melfi / replisa.com. 2026-07-22 |
| 5.2.3 | Script demo live 5 minuti (cosa mostrare, in che ordine) | `P1` | 1 | ✅ | `docs/DEMO-SCRIPT.md`: checklist pre-demo, sceneggiatura minuto-per-minuto (Benvenuto → Promemoria & Scadenze → Campagne + dashboard + chiusura CTA), piano B se qualcosa va storto, cosa NON fare. Allineato ai flussi attuali. 2026-07-22 |
| 5.2.4 | Video demo registrato (screen recording con voice-over) | `P3` | 1 | ⬜ | Post-MVP, quando i flussi girano |

---

## E6 — Go-to-Market & Primo Cliente

> **Obiettivo:** Primo cliente pagante entro 10-12 settimane dal kickoff.

### E6.1 — Outreach

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 6.1.1 | Lista 20-30 PMI target nella tua zona (Vittoria + provincia) | `P0` | 2 | 🟡 | Griglia pronta da compilare: `docs/PMI-TARGET.md` (guida + tabella con colonne allineate agli stati `/admin/leads`) + `docs/pmi-target.csv` (per foglio di calcolo). **Resta da compilare col territorio** (conoscenza dell'utente). 2026-07-22 |
| 6.1.2 | Invio primo batch di outreach (10 contatti) | `P0` | 1 | ⬜ | |
| 6.1.3 | Follow-up dopo 3-5 giorni sui non-rispondenti | `P1` | 1 | ⬜ | |
| 6.1.4 | Demo live al primo interessato | `P0` | 1 | ⬜ | Usare lo script E5.2.3 |

### E6.2 — Onboarding Primo Cliente

| # | Task | Priorità | SP | Stato | Note |
|---|------|:--------:|:--:|:-----:|------|
| 6.2.1 | Processo onboarding documentato (checklist step-by-step) | `P1` | 2 | ✅ | `docs/ONBOARDING-CHECKLIST.md`: 6 fasi (crea account admin/self-service → dati Meta → collega numero → template → configura flussi → test e2e) + checklist rapida copia-incolla. Collega DEMO-SCRIPT/META-TEMPLATES-SUBMISSION/GO-LIVE. 2026-07-22 |
| 6.2.2 | Contratto di servizio / T&C base | `P1` | 2 | ⬜ | Consulta commercialista per P.IVA |
| 6.2.3 | Setup account cliente sulla piattaforma | `P0` | 1 | ✅ | Form `/whatsapp` (`WhatsAppSettings`) per collegare il numero (phone_number_id/waba_id/access_token criptato + verifica live). Censimento da admin: "Nuovo cliente" in `/admin/tenants` crea tenant + owner + licenza offline. 2026-06-20/21 |
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
| ICE-11 | Embedded Signup per onboarding self-service WABA | E1/E4 | Onboarding cliente in ~5 min via JS SDK Meta. Richiede App Review (advanced access su `whatsapp_business_management` + `whatsapp_business_messaging`). Lead time 2-4 settimane. Necessario quando >5 clienti — vedi ADR-003 |

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
