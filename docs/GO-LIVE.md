# Go-Live — punto della situazione e runbook

> **Aggiornato:** 2026-07-22. Fotografia di cosa è pronto e cosa manca per portare in produzione
> tutto il lavoro accumulato su `develop`/`main`. Complementare a `deploy/README.md` (dettaglio comandi VPS).

---

## Verdetto in una riga

Il **codice è completo e verde** (247 test), tutto committato e mergiato in `main`. Il gap verso il live è
**operativo**, non di sviluppo: *push → deploy → infra/config → sblocchi esterni (Stripe, Meta)*. Questi passi
li esegue l'utente (no credenziali push/deploy lato AI — vedi [[no-git-push-credentials]]).

> ⚠️ **Non posso verificare da qui cosa gira ora su replisa.com.** `push ≠ deploy`, e il deploy applicativo è
> manuale. Storicamente la prod è rimasta indietro rispetto al codice (vedi [[prod-behind-no-release]]): prima
> di dire "è live" va confermato sul VPS.

---

## 1. Stato Git

- `main` == `develop` (tutto mergiato, nessuna divergenza).
- **13 commit su `develop` e 26 su `main` non ancora pushati** su `origin`.
- Nulla è quindi ancora nemmeno sul remoto, tantomeno deployato.

**Azione:** `git push origin main develop` (utente).

---

## 2. Migration da eseguire in prod

Sono **19 file di migration** datati dal 2026-06-17 in poi (dopo il rilascio dell'engine Sprint 2); girano
col deploy, o `php8.4 artisan migrate --force`. `migrate:status` sul VPS resta la fonte autorevole su cosa
è davvero `Pending`.

- Auth/ruoli: `add_tenant_id_to_users`, tabelle permessi Spatie
- Billing (Cashier): `customer_columns`, `subscriptions`, `subscription_items` (+ meter)
- `add_fiscal_fields_to_tenants`, `add_offline_license_to_tenants`, `add_sector_columns`, `add_reviews_addon_to_tenants`
- `campaigns`, `deadlines`, `deadline_reminders`
- `personal_access_tokens` (Sanctum, API v1)
- `leads` (form contatto landing)
- `tags` + `contact_tag` (segmentazione contatti, E3.4.3)
- `add_tag_id_to_campaigns` (segmento della campagna, E3.4.3)
- `review_clicks` (short-link tracciati recensioni, E3.3.3)

**Seeder da lanciare una volta:** `RoleSeeder` (ruoli super-admin/owner/operator), `NationalDeadlinesSeeder`
(scadenze fiscali 2026 — da verificare che le date siano ancora corrette).

---

## 3. Variabili `.env` da impostare nell'overlay prod

> L'`.env` di prod vive in `~/.dploy/overlays/.env`; dopo ogni modifica: `php8.4 artisan config:clear` (vedi [[dploy-env-overlay]]).

- [ ] `APP_LOCALE=it`
- [ ] **SMTP reale** (`MAIL_MAILER=smtp` + host/port/user/pass/from) — serve per gli **inviti clienti** e le
      **notifiche lead**; in locale è `log`, in prod senza SMTP quelle email non partono
- [ ] `CONTACT_NOTIFY_EMAIL` — destinatario delle richieste demo dal form landing
- [ ] `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY=eur`, `CASHIER_CURRENCY_LOCALE=it_IT`
- [ ] I **9 Price ID Stripe**: `STRIPE_PRICE_{STARTER,BASE,PRO,BUSINESS}` + `_ANNUAL` + `STRIPE_PRICE_REVIEWS_ADDON`
- [ ] Token Meta permanente valido (System User, scope messaging+management) — verificare non sia scaduto

---

## 4. Infrastruttura VPS (dettaglio in `deploy/README.md`)

- [ ] **Queue worker** systemd attivo (`replisa-worker`) — senza, il webhook Meta accoda e non processa
- [ ] **Cron** `schedule:run` ogni minuto — senza, non partono reminder/scadenze/recensioni/campagne schedulate
- [ ] **`npm ci && npm run build`** nel deploy — la landing e la dashboard usano asset Vite
- [ ] `shared/storage/{app/public,logs}` esistenti (bug DPLOY noto — vedi [[shared-storage-non-popolata]])
- [ ] **Webhook Meta** sottoscritto: callback su `/webhook`, verify token, campo `messages`

---

## 5. Blocchi esterni (non risolvibili nel codice)

| Blocco | Impatto se manca | Chi/dove |
|---|---|---|
| **Prodotti + Price ID su Stripe** | Il checkout/abbonamenti non funzionano; `PlanLimits` cade sul default | `scripts/stripe-setup.sh` crea prodotti+prezzi e stampa il blocco `.env` (test di default, `--live` per la prod). Le chiavi `STRIPE_KEY/SECRET/WEBHOOK_SECRET` restano da copiare a mano dalla dashboard |
| **Template Meta approvati** | `appointment_reminder` e `review_request` non partono (reminder/recensioni muti); campagne/scadenze idem finché non c'è almeno un template | WhatsApp Manager, per-WABA (ADR-003). Runbook: `META-TEMPLATES-SUBMISSION.md` (spec: `META-TEMPLATES.md`) |
| **Revisione legale T&C/Privacy** | Rischio legale coi primi paganti | Le pagine legali sono bozze (E6.2.2) |
| **Numero WhatsApp del cliente** | Nessun invio reale finché un tenant non collega il suo WABA | Form `/whatsapp` (onboarding) |

---

## 6. Cosa è pronto lato prodotto (per contesto)

Completo e testato: engine WhatsApp + webhook, i 3 flussi (Benvenuto, Promemoria & Scadenze, Campagne),
add-on Recensioni, multi-tenancy + isolamento, auth + ruoli, area admin (tenant, licenze offline, lead,
scadenze nazionali), billing Stripe/Cashier (codice), dashboard cliente completa (overview + grafico + costi
+ contatti/conversazione + appuntamenti + log filtrabile), API pubblica v1 (Sanctum) + docs, landing con form
contatto + cookie banner + SEO/JSON-LD. Materiale commerciale: brochure, script demo, template outreach,
griglia PMI.

---

## 7. Sequenza minima consigliata per il primo cliente

1. `git push origin main develop`.
2. Setup Stripe (prodotti + Price ID) → overlay `.env` → `config:clear`.
3. SMTP nell'overlay (inviti + notifiche lead).
4. `dploy deploy main` → verifica migration girate.
5. Verifica infra: worker attivo, cron attivo, webhook Meta sottoscritto.
6. Sottometti i template Meta (`appointment_reminder`, `review_request`) e attendi l'approvazione.
7. Smoke test live: registrazione → collega numero → invio di prova → log/stato.
8. Far revisionare le pagine legali prima di firmare col primo pagante.
