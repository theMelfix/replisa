# Go-Live — stato produzione e residui

> **Aggiornato:** 2026-07-28. **Replisa è LIVE su replisa.com dal ~2026-07-24.** Questo file era il
> runbook pre-lancio; ora è la fotografia di *cosa è in produzione e verificato* e *cosa resta* prima di
> vendere al primo cliente reale. Complementare a `deploy/README.md` (comandi VPS) e alla memoria
> `prod-live-status`.

---

## Verdetto in una riga

L'app è **deployata e funzionante in produzione**: push → deploy → migration → infra sono **fatti** e lo
smoke test end-to-end è passato. Il gap residuo verso il **primo cliente pagante** è tutto **esterno/manuale**
(Stripe live, template Meta della WABA cliente, SMTP, revisione legale) più l'**onboarding di un tenant reale**
— finora tutto è stato verificato solo sul tenant *Sandbox*. Push/deploy li esegue l'utente
([[no-git-push-credentials]]); il deploy ora parte dalla **dploy-dashboard** ([[prod-deploy-cloudpanel-dploy]]).

> ⚠️ **`push ≠ deploy` vale ancora per le modifiche future.** Il codice pushato non è in prod finché non fai
> il deploy dalla dashboard. Prima di dire che una feature nuova "è live", verifica sul VPS
> (`git log origin/main`, `migrate:status`, smoke test).

---

## ✅ Fatto e verificato in produzione

- **Git:** `main` == `develop`, tutto pushato su `origin` (0 commit di scarto).
- **Deploy:** release applicata sul VPS; `/up` risponde 200.
- **Migration:** tutte girate, **zero pendenti** (`migrate:status` pulito). Include `tags`/`contact_tag`/
  `add_tag_id_to_campaigns` (E3.4.3) e `review_clicks` (E3.3.3), le ultime feature.
- **Infra VPS:** **queue worker** systemd attivo, **cron `schedule:run`** ogni minuto armato
  (reminder/scadenze/recensioni/campagne orarie), **webhook Meta** sottoscritto su `/webhook` (campo `messages`).
- **Flussi live (tenant Sandbox, Test WABA `1333683675528873`):**
  - **Welcome Flow** — messaggio al numero business → menu interattivo di ritorno.
  - **Promemoria appuntamenti** — template inviato → arrivo coi bottoni → tocco *Confermo* → webhook →
    appuntamento a `confirmed`.
- **Template Meta:** **`appointment_reminder` APPROVATO** sulla Test WABA.
- **Billing Stripe (modalità TEST) verificato e2e su prod (2026-07-28):** account + 4 prodotti piano + add-on
  Recensioni + 9 prezzi (test), 9 Price ID nell'overlay, endpoint webhook prod (`we_1Ty7LC` → `/stripe/webhook`).
  Checkout con carta test su replisa.com → `customer.subscription.created` consegnato (`pending_webhooks=0`) →
  subscription sincronizzata (banner prova via) + Billing Portal ok. Fix del checkout Livewire deployato.
  Dettaglio in memoria `stripe-billing-status`.

> Nota: i flussi sono stati accesi **via tinker** sul tenant Sandbox (id 1), che non ha owner. In esercizio
> reale i flussi si configurano da `/automations` (per-owner). Vedi `prod-live-status` in memoria.

---

## ⏳ Residui verso il primo cliente reale

| Residuo | Impatto se manca | Chi/dove |
|---|---|---|
| **Onboarding tenant reale** | Finora tutto verificato solo su Sandbox: nessun cliente vero collegato | `docs/ONBOARDING-CHECKLIST.md` (6 fasi) |
| **Stripe LIVE** (billing reale) | Finora è tutto in **test**: nessun incasso reale | Attivare l'account, ricreare prodotti/prezzi in **live** (Price ID diversi) — `scripts/stripe-setup.sh --live` è **pronto e allineato** (2026-07-29: `tax_behavior=unspecified`, niente dipendenza da `jq`) — poi chiavi `pk_live`/`sk_live` + webhook endpoint live → overlay + `dploy deploy main`. **Regime forfettario: nessuna IVA** → prezzi as-is, niente Stripe Tax. Fattura elettronica (dicitura forfettario) **fuori da Stripe**. |
| **SMTP reale nell'overlay** | Inviti clienti e notifiche lead dal form landing **non partono** (in locale è `log`) | `~/.dploy/overlays/.env` → `MAIL_*` + `config:clear` ([[dploy-env-overlay]]) |
| **Template `review_request`** | La Richiesta recensione (add-on) non parte | WhatsApp Manager, per-WABA. Runbook: `META-TEMPLATES-SUBMISSION.md` |
| **Template sulla WABA del cliente** | `appointment_reminder` è approvato solo sulla *Test* WABA: va rifatto per ogni WABA reale (ADR-003) | Idem, per ogni cliente |
| **Revisione legale T&C/Privacy** | Rischio legale coi primi paganti | Pagine legali ancora bozze (E6.2.2) |
| **Embedded Signup / Tech Provider** | Onboarding self-service del numero cliente resta manuale | Richiede App Review Meta (Icebox ICE-11) |

---

## Variabili `.env` overlay — da confermare/impostare per l'esercizio reale

> Overlay in `~/.dploy/overlays/.env`; dopo ogni modifica `php8.4 artisan config:clear` ([[dploy-env-overlay]]).

- `APP_LOCALE=it`
- **SMTP** (`MAIL_MAILER=smtp` + host/port/user/pass/from) — vedi residui
- `CONTACT_NOTIFY_EMAIL` — destinatario richieste demo dal form landing
- `STRIPE_KEY`, `STRIPE_SECRET`, `STRIPE_WEBHOOK_SECRET`, `CASHIER_CURRENCY=eur`, `CASHIER_CURRENCY_LOCALE=it_IT` — **impostati in TEST** nell'overlay; per il live vanno sostituiti con `pk_live`/`sk_live` + `whsec` dell'endpoint live
- I **9 Price ID**: `STRIPE_PRICE_{STARTER,BASE,PRO,BUSINESS}` + `_ANNUAL` + `STRIPE_PRICE_REVIEWS_ADDON` — **valorizzati (test)**; in live cambiano
- ⚠️ Il `.env` di prod è un **file copiato al deploy** (non symlink): l'overlay si applica solo con `dploy deploy main`, non col solo `config:clear`
- Token Meta permanente valido (System User, scope messaging+management) — verificare non sia scaduto

---

## Sequenza per il primo cliente reale

1. Setup Stripe **live** (attiva account forfettario, ricrea prodotti + Price ID in live, chiavi/webhook live) → overlay → **`dploy deploy main`** (l'overlay si applica solo col redeploy).
2. SMTP nell'overlay (inviti + notifiche lead) → `config:clear`.
3. Crea il tenant del cliente (`/admin/tenants` o registrazione self-service) — settore obbligatorio.
4. Collega la WABA del cliente in `/whatsapp` + Verifica connessione.
5. Sottometti i template nella **WABA del cliente** (`appointment_reminder`; `review_request` se vende l'add-on)
   e attendi l'approvazione.
6. Il cliente configura i flussi da `/automations` / `/scadenze` / `/appointments`.
7. Smoke test live col numero reale: benvenuto + reminder + conferma.
8. Far revisionare le pagine legali prima di firmare col primo pagante.

---

## Cosa è pronto lato prodotto (per contesto)

Completo e testato: engine WhatsApp + webhook, i 3 flussi (Benvenuto, Promemoria & Scadenze, Campagne),
add-on Recensioni, multi-tenancy + isolamento, auth + ruoli, area admin (tenant, licenze offline, lead,
scadenze nazionali), billing Stripe/Cashier (codice), dashboard cliente completa (overview + grafico + costi
+ contatti/conversazione + appuntamenti + log filtrabile), API pubblica v1 (Sanctum) + docs, landing con form
contatto + cookie banner + SEO/JSON-LD. Materiale commerciale: brochure, script demo, template outreach,
griglia PMI.
