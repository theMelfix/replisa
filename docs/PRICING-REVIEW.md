# Revisione prezzi Replisa — analisi concorrenza & piano

> Stato: **decisioni applicate** (2026-06-23).
> Prezzi in `config/plans.php`: Starter €19 · Base €49 · Pro €99 · Business €199 (mensile) — annuale -20% · add-on Recensioni €19 · prova 14 giorni.

## 1. Benchmark concorrenza (giu 2026)

| Tool | Mercato | Piani /mese | Modello | Note |
|---|---|---|---|---|
| Spoki | IT, WhatsApp marketing | Free · €19 (Service) · €39 (Marketing) · €55 (Sales) | Tiered flat + Meta a parte | Concorrente più diretto; sconto annuale; automazioni illimitate |
| Callbell | IT, customer care multicanale | Free · €14–18 **per agente** + €50 ogni 10 agenti (WA) | Per-agente | Broadcast €0,02/contatto; bot builder +€59; canale extra +€20 |
| Wati | Internazionale, WA API | ~€55 (Growth) · €110 (Pro) · €279 (Business) | Tiered + **markup ~20%** sui messaggi | Utenti extra $39-89; conto reale +30-50% per add-on |
| Gestionali verticali (Primo, VenereBCloud, CutApp) | IT, parrucchieri/estetisti/studi | €6,90–12 (o €60/anno) | Suite gestionale; WA reminder = feature | Economici ma prodotto diverso (gestionale completo) |

Costi conversazione Meta sempre **a parte** in tutti i tool orizzontali.

## 2. Insight

1. **Due segmenti**: orizzontale (Spoki/Wati/Callbell €19-279) vs verticali gestionali (€7-12). Replisa gioca nell'orizzontale → non confrontarsi sul prezzo coi verticali.
2. La proposta **29/69/129/249** è allineata a Spoki/Wati. Posizionamento corretto.
3. **Manca tier Free/Trial** (lo hanno Spoki e Callbell): leva di acquisizione mancante.
4. **Leve di margine non sfruttate**: i concorrenti monetizzano i messaggi (Wati +20%, Callbell €0,02/contatto). Replisa passa Meta a costo → le **Campagne** sono il punto naturale per un markup o pacchetti messaggi.
5. **Annuale -20/25%** è prassi di mercato.

## 3. Decisioni prese (2026-06-23)

- [x] **Tier Free/Trial** → **Prova 14 giorni** senza carta (piano Pro durante la prova).
- [x] **Monetizzazione campagne** → **Pacchetti inclusi + extra a consumo**: messaggi campagna/mese per piano (Base 1.000, Pro 5.000, Business 20.000). Enforcement del pacchetto fatto; **overage a consumo = follow-up** (metered billing Stripe).
- [x] **Annuale** → **Sì, -20%** (price_annual + toggle mensile/annuale).
- [x] **Prezzi finali** → **19 / 49 / 99 / 199** (più aggressivi, vicini all'entry Spoki).
- [x] **Add-on** → Recensioni €19 confermato.
- [ ] **Modello multi-operatore**: ancora da definire (per ora "Multi-operatore" è una feature del piano Pro+, senza prezzo per-postazione).

### Follow-up implementazione
- **Overage campagne a consumo** (Stripe metered billing) oltre il pacchetto incluso.
- **Price ID Stripe** mensili e **annuali** da creare e mettere in `.env` (`STRIPE_PRICE_*` e `STRIPE_PRICE_*_ANNUAL`).

## 4. Deliverable (una volta prese le decisioni)

1. Aggiornare `config/plans.php` (prezzi, limiti, eventuale tier free, add-on, annuale).
2. Aggiornare la landing (`welcome.blade.php`) sezione prezzi.
3. Creare i Prezzi corrispondenti su Stripe e popolare `STRIPE_PRICE_*`.
4. Changelog + backlog.

## 5. Cadenza di monitoraggio

Rivedere i prezzi dei concorrenti (Spoki, Callbell, Wati) ogni 3-6 mesi e a ogni cambio di tariffe Meta. Aggiornare questo documento.

## Fonti
- Spoki — https://spoki.com/it/prezzi
- Callbell — https://www.callbell.eu/en/pricing/
- Wati — https://www.wati.io/pricing/
- Guida costi WhatsApp Business API — https://respond.io/blog/whatsapp-business-api-pricing
