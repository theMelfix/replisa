# Changelog Replisa

Log cronologico (append-only) di tutto il lavoro completato. Una riga per task del backlog.

Formato: `YYYY-MM-DD · <task-code> · <commit-sha> · <descrizione breve>`

---

## Sprint 1 — Fondamenta (Settimane 1-2)

- 2026-05-14 · 1.1.1 · `ea35082` · Scaffold Laravel app (MySQL + Pest, no starter kit auth)
- 2026-05-14 · 1.1.2 · `ea35082` · Git repo `theMelfix/notifly` (privato), branch `main`/`develop`/`master`, `.gitignore` Laravel + esclusione `.claude/settings.local.json`
- 2026-05-14 · 1.1.3 · `ea35082` · `.env`/`.env.example` con placeholder Meta WhatsApp Cloud API (`META_GRAPH_API_VERSION`, `META_WHATSAPP_PHONE_NUMBER_ID`, `_BUSINESS_ACCOUNT_ID`, `_ACCESS_TOKEN`, `_APP_SECRET`, `_WEBHOOK_VERIFY_TOKEN`)
- 2026-05-14 · ADR-003 · `7c951a7` · Multi-tenant credential model: una Meta App Notifly (Tech Provider), credenziali app-level in `.env`, credenziali per-tenant in tabella `tenants`. `.env.example` ristrutturato. Aggiunto ICE-11 (Embedded Signup) all'Icebox
- 2026-05-15 · 1.2.1 · — · Meta Business App `Notifly` creata (app type Business, use case "Manage messaging on WhatsApp Business Platform")
- 2026-05-15 · 1.2.2 · — · Prodotto WhatsApp attivato; Test WABA `1333683675528873` + numero sandbox creati
- 2026-05-15 · 1.2.3 · — · Raccolti `PHONE_NUMBER_ID=1147055808484414` e `WABA_ID=1333683675528873`. Numero personale +39 338 485 2605 verificato come tester. Bump `META_GRAPH_API_VERSION=v25.0` (era v21.0, in scadenza)
- 2026-05-15 · 1.2.4 · — · System User Token permanente generato (scope `whatsapp_business_messaging` + `_management`, accesso solo Test WABA). Salvato in `.env` come `META_WHATSAPP_SANDBOX_ACCESS_TOKEN`
- 2026-05-15 · sec · — · App Secret resettato (era stato esposto in chat). Grace period 0h
- 2026-05-15 · 1.2.5 · — · Primo invio template `hello_world` riuscito (HTTP 200, message_status=accepted). Script bash riusabile in `scripts/whatsapp-test-send.sh`
- 2026-05-15 · ADR-004 · — · Rename prodotto **Notifly → Replisa** (notifly.it/.com/.app non disponibili a prezzo standard; 18 alternative valutate). Acquistati `replisa.com` (primario) + `replisa.it` (difensivo 301→.com). Backlog rinominato in `replisa_backlog.md`. Repo GitHub e Meta App da rinominare manualmente
- 2026-05-15 · infra · — · Cartella locale rinominata `/home/gmelfi/notifly/` → `/home/gmelfi/replisa/`
- 2026-05-15 · 1.1.5 · — · VPS IONOS con CloudPanel: site PHP `replisa.com` (PHP 8.4, Laravel template). DNS IONOS: A `@`/`www`/`app`/`api` → VPS IP. SSL Let's Encrypt attivo su `replisa.com` + `www`. Site secondario `replisa.it` come Static Site con redirect 301 NGINX → `replisa.com` + SSL incluso
- 2026-05-15 · 1.2.6 · — · Bitwarden cloud (free tier) attivato. Credenziali Meta App, WABA, System User Token, Webhook Verify Token salvate
- 2026-05-15 · 1.1.4 · — · MariaDB su VPS IONOS. DB `replisa` (charset utf8mb4) + user `replisa_app` con privilegi solo sul DB, bind localhost. Creati via CloudPanel UI. Credenziali in Bitwarden
- 2026-05-16 · 1.1.6 · — · Deploy pipeline DPLOY (CloudPanel) configurata. Release atomiche Capistrano-style su `/home/replisa-com/htdocs/replisa.com/{releases,shared,current}`. Shared: `storage/app` + `storage/logs`. Overlays: `.env` produzione (APP_KEY generato con `openssl`). Sudoers: `replisa-com` può `systemctl reload php8.4-fpm` senza password. Vhost NGINX CloudPanel: `{{root}}` sostituito con `current/public` (HTTP 443 + 8080 internal). Primo deploy `main`: **https://replisa.com serve Laravel 13 default page** 🎉 Sprint 1 chiuso definitivamente

## Sprint 2 — Motore & Comunicazione (Settimane 3-4)

- 2026-06-08 · 2.3.1 · — · Migration + model `Tenant`: `name`, `phone_number_id` (unique), `waba_id`, `access_token` (cast `encrypted`, `hidden` — ADR-003), `plan`, `active`. Relazioni `contacts()`/`messages()`. Verificato cast encrypted a riposo via tinker
- 2026-06-08 · 2.3.2 · — · Migration + model `Contact`: FK `tenant_id` (cascade), `phone`, `name`, `opted_in`+`opted_in_at` (GDPR), `last_seen_at`. Unique `(tenant_id, phone)` — numero unico per tenant, non globale
- 2026-06-08 · 2.3.3 · — · Migration + model `Message`: FK `tenant_id`+`contact_id` (cascade), `direction` (outbound/inbound), `type`, `content` (json), `status`, `meta_message_id` (index per match status webhook), `error` (json)
- 2026-06-08 · 2.1.1-2.1.6 · — · `WhatsAppService` (E2.1 completo): wrapper Meta Cloud API scoped per-tenant (`::for($tenant)`, ADR-003). `sendTemplate()` (componenti dinamici), `sendText()`, `sendButtons()` (max 3) e `sendList()` (max 10) con validazione. Retry+backoff esponenziale solo su errori transitori (429/5xx/connessione) via `Http::retry`; `WhatsAppApiException` con `metaCode`/`httpStatus` sui permanenti. Ogni invio loggato su `messages` (queued→sent+meta_message_id / failed+error). Config `services.meta.*`. Coperto da 7 test Pest con `Http::fake` (payload, logging, retry, errori, validazione)
- 2026-06-09 · infra · `198b5b8` · Queue worker infra-as-code: `deploy/replisa-worker.service` (systemd, `queue:work` coda database) + `deploy/README.md` (setup worker, restart post-deploy via sudoers, checklist deploy engine, nota scheduler E3.2). `main` allineato a Sprint 2 (merge `6cd69d4`); push + esecuzione deploy/worker/Meta-webhook sul VPS a carico del dev (vedi runbook)
- 2026-06-09 · 2.3.4-2.3.6 · — · Schema P1 completato (E2.3 chiuso). `conversations` (finestre billing 24h: category, billable, opened_at/expires_at), `automations` (flussi per-tenant: type+trigger+config json, unique tenant+type, costanti TYPE_*), `appointments` (scheduled_at, status, reminded_at, review_requested, costanti STATUS_*). Relazioni inverse aggiunte a `Tenant`/`Contact`. Cast e cascade delete verificati via tinker
- 2026-06-09 · 2.2.1-2.2.6 · — · Webhook Meta (E2.2 completo). `GET /webhook` verifica challenge (`hash_equals` sul verify token); `POST /webhook` riceve eventi. Route in `routes/webhook.php` registrate fuori dal gruppo `web` (no CSRF/sessione) via `then:` in bootstrap. Firma `X-Hub-Signature-256` validata dal middleware `meta.signature` (HMAC-SHA256 del body raw con app_secret, fail-closed). Processing async via job `ProcessWhatsAppWebhook` (idempotente su `meta_message_id`): parsing messaggi inbound (text/interactive/button) con upsert contatto + `last_seen_at`, e status update (delivered/read/failed → colonna `status`/`error`). Endpoint risponde `EVENT_RECEIVED` (200) subito. Coperto da 11 test Pest. ⚠️ NB: in produzione serve un queue worker attivo (`queue:work`, connection `database`) — da aggiungere al deploy DPLOY
