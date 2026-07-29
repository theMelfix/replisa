#!/usr/bin/env bash
#
# Crea i prodotti e i prezzi Stripe di Replisa (E4.2.6 / go-live) e stampa
# il blocco .env con i Price ID da incollare nell'overlay di produzione.
#
# Importi allineati a config/plans.php (2026-07-22):
#   Starter  €19/mese  €182/anno       Pro       €99/mese  €950/anno
#   Base     €49/mese  €470/anno       Business €199/mese €1910/anno
#   Add-on Recensioni €19/mese
#
# Prerequisiti:
#   - Stripe CLI  → https://stripe.com/docs/stripe-cli   (`stripe login`)
#   - jq **oppure** python3 (per leggere l'id dalla risposta JSON)
#
# Uso:
#   ./scripts/stripe-setup.sh            # MODALITÀ TEST (default, sicura)
#   ./scripts/stripe-setup.sh --live     # crea in PRODUZIONE (chiede conferma)
#
# NB: lo script NON è idempotente. Eseguito due volte crea prodotti/prezzi
#     duplicati. Se sbagli, archivia i vecchi da dashboard e rilancia.

set -euo pipefail

# --- Modalità test/live -------------------------------------------------------
LIVE_FLAG=""
MODE="TEST"
if [[ "${1:-}" == "--live" ]]; then
    LIVE_FLAG="--live"
    MODE="LIVE (produzione)"
fi

# --- Controlli prerequisiti ---------------------------------------------------
command -v stripe >/dev/null || { echo "❌ Stripe CLI non trovato. Installa: https://stripe.com/docs/stripe-cli"; exit 1; }

# Estrae .id dal JSON su stdin. Usa jq se c'è, altrimenti python3 (su questa
# macchina jq non è installato) — così lo script gira senza dipendenze extra.
if command -v jq >/dev/null; then
    json_id() { jq -r '.id'; }
elif command -v python3 >/dev/null; then
    json_id() { python3 -c 'import json,sys; print(json.load(sys.stdin)["id"])'; }
else
    echo "❌ Serve jq oppure python3 per leggere la risposta JSON di Stripe."; exit 1
fi

echo "▶ Creazione prodotti/prezzi Stripe in modalità: $MODE"
if [[ -n "$LIVE_FLAG" ]]; then
    read -r -p "⚠️  Stai per creare prodotti REALI in produzione. Continuare? (scrivi 'si'): " ok
    [[ "$ok" == "si" ]] || { echo "Annullato."; exit 1; }
fi

CURRENCY="eur"

# create_product "Nome" "Descrizione" → stampa l'id prodotto
# (i comandi Stripe CLI stampano già la risposta API in JSON: la passiamo a jq)
create_product() {
    stripe products create $LIVE_FLAG \
        --name "$1" \
        --description "$2" | json_id
}

# create_price PRODUCT_ID AMOUNT_CENTS INTERVAL LOOKUP_KEY → stampa l'id prezzo
#   INTERVAL = month | year.
#   tax_behavior=unspecified: l'esercente è in **regime forfettario** (nessuna IVA
#   da esporre) → niente Stripe Tax, il prezzo mostrato è quello incassato.
#   Allineato ai prezzi già creati a mano in test. La fattura elettronica con la
#   dicitura forfettario si gestisce fuori da Stripe (SdI).
create_price() {
    stripe prices create $LIVE_FLAG \
        --product "$1" \
        --currency "$CURRENCY" \
        --unit-amount "$2" \
        -d "tax_behavior=unspecified" \
        -d "recurring[interval]=$3" \
        -d "lookup_key=$4" | json_id
}

echo "→ Creo i prodotti…"
P_STARTER=$(create_product  "Replisa Starter"  "Piano Starter — 1 automazione, fino a 500 contatti")
P_BASE=$(create_product     "Replisa Base"     "Piano Base — tutte le automazioni, fino a 2.000 contatti")
P_PRO=$(create_product      "Replisa Pro"      "Piano Pro — contatti illimitati, API, multi-operatore")
P_BUSINESS=$(create_product "Replisa Business" "Piano Business — tutto Pro + Recensioni Google incluse")
P_REVIEWS=$(create_product  "Replisa — Add-on Recensioni Google" "Richiesta recensione automatica dopo un appuntamento")

echo "→ Creo i prezzi mensili e annuali…"
PRICE_STARTER=$(create_price  "$P_STARTER"  1900   month "replisa_starter_monthly")
PRICE_BASE=$(create_price     "$P_BASE"     4900   month "replisa_base_monthly")
PRICE_PRO=$(create_price      "$P_PRO"      9900   month "replisa_pro_monthly")
PRICE_BUSINESS=$(create_price "$P_BUSINESS" 19900  month "replisa_business_monthly")

PRICE_STARTER_ANNUAL=$(create_price  "$P_STARTER"  18200   year "replisa_starter_annual")
PRICE_BASE_ANNUAL=$(create_price     "$P_BASE"     47000   year "replisa_base_annual")
PRICE_PRO_ANNUAL=$(create_price      "$P_PRO"      95000   year "replisa_pro_annual")
PRICE_BUSINESS_ANNUAL=$(create_price "$P_BUSINESS" 191000  year "replisa_business_annual")

PRICE_REVIEWS=$(create_price "$P_REVIEWS" 1900 month "replisa_reviews_addon_monthly")

# --- Output: blocco .env pronto da incollare ---------------------------------
cat <<ENV

✅ Fatto ($MODE). Incolla questo nell'overlay .env di prod (~/.dploy/overlays/.env),
   poi lancia un **redeploy**: dploy deploy main

   ⚠️  Il .env di prod è un file COPIATO al deploy (non un symlink): modificare
       l'overlay non basta, e nemmeno il solo config:clear — serve il redeploy.

# --- Stripe Price ID (generati $(date +%Y-%m-%d), modalità $MODE) ---
STRIPE_PRICE_STARTER=$PRICE_STARTER
STRIPE_PRICE_BASE=$PRICE_BASE
STRIPE_PRICE_PRO=$PRICE_PRO
STRIPE_PRICE_BUSINESS=$PRICE_BUSINESS

STRIPE_PRICE_STARTER_ANNUAL=$PRICE_STARTER_ANNUAL
STRIPE_PRICE_BASE_ANNUAL=$PRICE_BASE_ANNUAL
STRIPE_PRICE_PRO_ANNUAL=$PRICE_PRO_ANNUAL
STRIPE_PRICE_BUSINESS_ANNUAL=$PRICE_BUSINESS_ANNUAL

STRIPE_PRICE_REVIEWS_ADDON=$PRICE_REVIEWS

ENV

if [[ -z "$LIVE_FLAG" ]]; then
    echo "ℹ️  Questi sono Price ID di TEST. Per la produzione rilancia con --live"
    echo "   e ricordati STRIPE_KEY/STRIPE_SECRET live + STRIPE_WEBHOOK_SECRET."
fi
