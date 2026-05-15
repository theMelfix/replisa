#!/usr/bin/env bash
# scripts/whatsapp-test-send.sh
# Test send di un template WhatsApp via Meta Cloud API usando le credenziali sandbox dal .env.
# Uso:  ./scripts/whatsapp-test-send.sh <numero_destinatario_E164_senza_+> [template_name]
# Es:   ./scripts/whatsapp-test-send.sh 393384852605 hello_world
#
# Prerequisiti:
# - .env popolato con META_GRAPH_API_VERSION, META_WHATSAPP_SANDBOX_PHONE_NUMBER_ID, META_WHATSAPP_SANDBOX_ACCESS_TOKEN
# - Il numero destinatario deve essere già stato verificato nella dashboard Meta (Manage phone number list)
# - jq installato per pretty-print della risposta (opzionale)

set -euo pipefail

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ENV_FILE="${SCRIPT_DIR}/../.env"

if [[ ! -f "${ENV_FILE}" ]]; then
  echo "ERRORE: .env non trovato in ${ENV_FILE}" >&2
  exit 1
fi

# Carica le sole variabili META_* dal .env senza esporre tutto l'env del progetto.
set -a
# shellcheck disable=SC2046
eval "$(grep -E '^META_' "${ENV_FILE}" | sed 's/^/export /')"
set +a

: "${META_GRAPH_API_VERSION:?manca nel .env}"
: "${META_WHATSAPP_SANDBOX_PHONE_NUMBER_ID:?manca nel .env}"
: "${META_WHATSAPP_SANDBOX_ACCESS_TOKEN:?manca nel .env}"

TO="${1:?manca il numero destinatario in formato E.164 senza + (es. 393384852605)}"
TEMPLATE="${2:-hello_world}"

URL="https://graph.facebook.com/${META_GRAPH_API_VERSION}/${META_WHATSAPP_SANDBOX_PHONE_NUMBER_ID}/messages"

PAYLOAD=$(cat <<EOF
{
  "messaging_product": "whatsapp",
  "to": "${TO}",
  "type": "template",
  "template": {
    "name": "${TEMPLATE}",
    "language": { "code": "en_US" }
  }
}
EOF
)

echo "→ POST ${URL}"
echo "→ to=${TO}  template=${TEMPLATE}"
echo

RESPONSE=$(curl -sS -w "\n__HTTP_STATUS__:%{http_code}" -X POST "${URL}" \
  -H "Authorization: Bearer ${META_WHATSAPP_SANDBOX_ACCESS_TOKEN}" \
  -H "Content-Type: application/json" \
  -d "${PAYLOAD}")

HTTP_STATUS=$(echo "${RESPONSE}" | grep "__HTTP_STATUS__" | cut -d: -f2)
BODY=$(echo "${RESPONSE}" | sed '/__HTTP_STATUS__/d')

echo "HTTP ${HTTP_STATUS}"
if command -v jq >/dev/null 2>&1; then
  echo "${BODY}" | jq .
else
  echo "${BODY}"
fi

if [[ "${HTTP_STATUS}" =~ ^2 ]]; then
  echo
  echo "✅ Inviato. Controlla WhatsApp sul numero +${TO}."
else
  echo
  echo "❌ Fallito. Vedi error.code/message qui sopra. Errori comuni:"
  echo "   - 100 / 'Recipient phone number not in allowed list': aggiungi il numero in Meta dashboard"
  echo "   - 190: token scaduto o invalido"
  echo "   - 131009 / 131047: finestra 24h chiusa, usa un template (questo script lo fa già)"
  exit 1
fi
