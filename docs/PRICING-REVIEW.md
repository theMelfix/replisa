# Revisione prezzi Replisa — analisi concorrenza & piano

> Stato: **bozza/analisi** (2026-06-21). Nessuna modifica prezzi ancora applicata.
> Prezzi attuali in `config/plans.php`: Starter €29 · Base €69 · Pro €129 · Business €249 + add-on Recensioni €19.

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

## 3. Decisioni da prendere (prima di toccare i prezzi)

- [ ] **Tier Free/Trial**: free permanente limitato (es. 1 automazione, 100 contatti, no campagne) oppure trial 14 giorni? Imposta l'imbuto di acquisizione.
- [ ] **Monetizzazione campagne**: markup sui messaggi (stile Wati +X%), prezzo per contatto raggiunto (stile Callbell €0,02), o pacchetti messaggi inclusi per piano + extra a consumo?
- [ ] **Modello multi-operatore**: flat per piano (Business) o add-on per-postazione (stile Callbell)?
- [ ] **Annuale**: confermare sconto -20% e prezzi annuali.
- [ ] **Prezzi finali**: confermare 29/69/129/249 o ritoccare alla luce di Spoki (€39 Marketing) e Wati (€110 Pro).
- [ ] **Add-on**: Recensioni €19 — valutarne altri (es. operatore extra, pacchetto messaggi).

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
