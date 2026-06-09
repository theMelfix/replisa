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

## Scheduler (futuro — E3.2.2)

I reminder appuntamento richiederanno il Laravel Scheduler via cron:

```
* * * * * cd /home/replisa-com/htdocs/replisa.com/current && php8.4 artisan schedule:run >> /dev/null 2>&1
```

Da configurare quando si implementa E3.2.
