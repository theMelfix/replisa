# Changelog Notifly

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
