# Architecture Decision Records — Notifly

Formato breve: una sezione per decisione. Append-only (le decisioni superate vengono marcate "Superseded by ADR-N" ma non cancellate).

---

## ADR-001 · Stack tecnico iniziale

**Data:** 2026-05-14
**Stato:** Accepted

**Contesto:** Notifly è un SaaS one-man-dev per automazione WhatsApp B2B verso PMI italiane. Serve uno stack veloce da prototipare, con buon ecosistema per multi-tenant, queue, scheduler, auth.

**Decisione:** Laravel (PHP 8.4) + MySQL 8 + Pest. Niente starter kit auth in fase 1; Breeze/Jetstream valutati in Sprint 3-4 (E4.1.1).

**Alternative scartate:**
- Node/Nest: ecosistema multi-tenant meno maturo, dev più lento per uno solo.
- Django/Python: meno familiarità del dev, deploy su VPS PHP più semplice.

---

## ADR-002 · Branching strategy

**Data:** 2026-05-14
**Stato:** Accepted

**Contesto:** Repo iniziale aveva `master` e `develop`. Convenzione moderna usa `main`.

**Decisione:** `main` = default branch (production-ready), `develop` = integration branch per lavoro corrente. `master` resta temporaneamente per non rompere riferimenti, da rimuovere quando pulito.

**Alternative scartate:**
- Trunk-based (solo `main`): per un solo dev ok, ma `develop` separa il "WIP" dal "ship-ready" rendendo più chiaro cosa è demo-able.

---

## ADR-003 · Multi-tenant credential model (Tech Provider)

**Data:** 2026-05-14
**Stato:** Accepted

**Contesto:** Notifly serve N clienti. Ogni cliente ha la sua WhatsApp Business Account (WABA) e il suo numero. Va deciso quali credenziali Meta sono globali (Notifly) e quali sono per-cliente.

**Decisione:** Notifly opera come **Tech Provider**: una sola Meta App posseduta da theMelfix, a cui le WABA dei clienti vengono collegate come asset.

**Credenziali app-level** (in `.env`, identiche per tutti i tenant):
- `META_WHATSAPP_APP_ID`
- `META_WHATSAPP_APP_SECRET` — usato per validare firma webhook `X-Hub-Signature-256`
- `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN` — stringa custom uguale per tutti i tenant
- `META_GRAPH_API_VERSION`

**Credenziali tenant-level** (sulla tabella `tenants` — E2.3.1):
- `phone_number_id` — numero WhatsApp del cliente
- `waba_id` — WhatsApp Business Account ID del cliente
- `access_token` — token scoped alla WABA del cliente, **criptato a riposo** (Laravel `encrypted` cast)

**Onboarding clienti:**
- **Fase MVP (1-3 clienti):** manuale. Il cliente aggiunge theMelfix come Admin del suo Business Manager (o crea System User), token generato a mano e inserito nella tabella `tenants` dalla super-admin area (E4.1.4).
- **Fase scale (>5 clienti):** Embedded Signup (ICE-11). Richiede App Review Meta con scope `whatsapp_business_management` + `whatsapp_business_messaging` in advanced access (lead time 2-4 settimane).

**Per sandbox/test in dev:** il numero test sandbox e il suo token temporaneo (task 1.2.3) vengono inseriti come **tenant "default"** nel DB tramite seeder, non nel `.env`.

**Alternative scartate:**
- **Una Meta App per cliente (BYOA):** maggiore complessità operativa, ogni cliente dovrebbe creare e mantenere la sua app Meta. Inadatto a un SaaS B2B per PMI che non hanno competenze tecniche.
- **Tutto nel `.env`:** funziona per 1 tenant, rompe a 2.
