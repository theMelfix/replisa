# Deploy & Infra — Replisa

Produzione: VPS IONOS + CloudPanel. Deploy via **DPLOY** (release atomiche
Capistrano-style). Vedi task 1.1.6 nel backlog per il setup iniziale.

- Path release: `/home/replisa-com/htdocs/replisa.com/{releases,shared,current}`
- Utente di sistema: `replisa-com`
- PHP: `php8.4-fpm`
- DB: MariaDB locale (`replisa`)

---

## Queue worker (E2.2.6)

Il webhook Meta mette gli eventi su una coda `database` e li processa async.
**Senza un worker attivo gli eventi restano in coda non processati.**

### Setup iniziale (una tantum, come root)

```bash
cp /home/replisa-com/htdocs/replisa.com/current/deploy/replisa-worker.service /etc/systemd/system/
systemctl daemon-reload
systemctl enable --now replisa-worker
systemctl status replisa-worker     # verifica che sia "active (running)"
```

### Restart del worker ad ogni deploy

Il worker deve ripartire dopo ogni deploy per caricare il nuovo codice.
Aggiungere al `sudoers` (così l'utente di deploy può riavviarlo senza password):

```
replisa-com ALL=(root) NOPASSWD: /usr/bin/systemctl restart replisa-worker.service
```

E aggiungere alla pipeline DPLOY (hook post-deploy, accanto al reload di php-fpm):

```bash
sudo systemctl restart replisa-worker.service
```

---

## Checklist deploy di Sprint 2 (engine WhatsApp)

1. `main` aggiornato e pushato (DPLOY fa `git pull` da remoto).
2. **`.env` di produzione** (overlay DPLOY) popolato con le credenziali Meta:
   - `META_WHATSAPP_APP_ID`, `META_WHATSAPP_APP_SECRET`, `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN`
   - per i test sandbox: `META_WHATSAPP_SANDBOX_*` (in prod le credenziali reali vivono sulla tabella `tenants`)
   - `QUEUE_CONNECTION=database`
3. Lanciare il deploy: `dploy deploy main` (le migration girano automaticamente).
4. Setup/riavvio del **queue worker** (vedi sopra) — solo la prima volta per il setup.
5. Configurare il **webhook su Meta**: callback URL `https://api.replisa.com/webhook` (o `https://replisa.com/webhook`), verify token = `META_WHATSAPP_WEBHOOK_VERIFY_TOKEN`, subscribe al campo `messages`.
6. Smoke test: invio template via `scripts/whatsapp-test-send.sh` e verifica che lo status update arrivi (riga in `messages` che passa a `delivered`).

---

## Build frontend (E4.1+ — dashboard Breeze/Livewire)

Da Sprint 3/E4.1 l'app ha una UI (Breeze + Livewire + **Tailwind via Vite**):
servono gli asset compilati in `public/build/`. **Senza build, le pagine non
hanno stili.** Richiede Node sul VPS.

Aggiungere alla pipeline DPLOY (build step, dopo `composer install`):

```bash
npm ci && npm run build
```

Verifica post-deploy: esiste `public/build/manifest.json` ed è recente.

## Checklist rilascio E4.1 (Auth & Multi-Tenancy)

1. `main` aggiornato (merge `develop`→`main`) e pushato.
2. Deploy: `dploy deploy main` (composer + `npm ci && npm run build` + migrate).
3. **Migration nuove**: `users.tenant_id` + tabelle ruoli spatie (girano col deploy; in caso `php8.4 artisan migrate --force`).
4. **Ruoli + super-admin**:
   ```bash
   php8.4 artisan db:seed --class=RoleSeeder --force
   php8.4 artisan replisa:create-admin      # crea il tuo utente super-admin
   ```
5. **Dashboard su `app.replisa.com`**: vhost CloudPanel che punta alla stessa app (root `current/public`), SSL Let's Encrypt sul sottodominio `app`.
6. `php8.4 artisan config:clear` dopo modifiche al `.env`.

---

## Scheduler (E3.2 / E3.3 — ⚠️ da attivare sul VPS)

Due command girano **ogni ora** via Laravel Scheduler:
- `replisa:send-reminders` — reminder appuntamento (E3.2, -24h / -2h)
- `replisa:send-review-requests` — richiesta recensione post appuntamento completato (E3.3)

Entrambi sono coperti da **un'unica** entry in crontab (utente del sito), senza la
quale né reminder né richieste recensione partono:

```
* * * * * cd /home/replisa-com/htdocs/replisa.com/current && php8.4 artisan schedule:run >> /dev/null 2>&1
```

Verifica con `php8.4 artisan schedule:list`. Gli schedule sono definiti in
`routes/console.php` (`->hourly()->withoutOverlapping()`); i job sono idempotenti,
quindi run ravvicinati non generano invii duplicati.

> I flussi E3 partono solo se il tenant ha la relativa riga in `automations`
> attiva. Reminder e recensioni usano **template Meta approvati**
> (`appointment_reminder` UTILITY, `review_request` MARKETING): finché non sono
> approvati gli invii falliscono e vengono ritentati al giro dopo (nessun crash).
> Il Welcome Flow viaggia free-form nella finestra 24h, quindi non richiede template.
