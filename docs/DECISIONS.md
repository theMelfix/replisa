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
