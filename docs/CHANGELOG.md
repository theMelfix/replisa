# Changelog Notifly

Log cronologico (append-only) di tutto il lavoro completato. Una riga per task del backlog.

Formato: `YYYY-MM-DD · <task-code> · <commit-sha> · <descrizione breve>`

---

## Sprint 1 — Fondamenta (Settimane 1-2)

- 2026-05-14 · 1.1.1 · `ea35082` · Scaffold Laravel app (MySQL + Pest, no starter kit auth)
- 2026-05-14 · 1.1.2 · `ea35082` · Git repo `theMelfix/notifly` (privato), branch `main`/`develop`/`master`, `.gitignore` Laravel + esclusione `.claude/settings.local.json`
- 2026-05-14 · 1.1.3 · `ea35082` · `.env`/`.env.example` con placeholder Meta WhatsApp Cloud API (`META_GRAPH_API_VERSION`, `META_WHATSAPP_PHONE_NUMBER_ID`, `_BUSINESS_ACCOUNT_ID`, `_ACCESS_TOKEN`, `_APP_SECRET`, `_WEBHOOK_VERIFY_TOKEN`)
- 2026-05-14 · ADR-003 · — · Multi-tenant credential model: una Meta App Notifly (Tech Provider), credenziali app-level in `.env`, credenziali per-tenant in tabella `tenants`. `.env.example` ristrutturato. Aggiunto ICE-11 (Embedded Signup) all'Icebox
