#!/usr/bin/env bash
# Fetches Sanctum bearer tokens via POST /api/auth/login (Adventurer app auth).
# Requires curl, jq, and the API running. Accounts must exist in `adventurers`.
#
# Usage:
#   chmod +x scripts/fetch-app-tokens.sh
#   ./scripts/fetch-app-tokens.sh
#   KARARIF_BASE_URL=https://api.example.com ./scripts/fetch-app-tokens.sh
#
# Override credentials:
#   export KARARIF_CREATOR_EMAIL=... KARARIF_CREATOR_PASSWORD=...
#   export KARARIF_PLAYER_EMAIL=... KARARIF_PLAYER_PASSWORD=...

set -euo pipefail

BASE_URL="${KARARIF_BASE_URL:-http://127.0.0.1:8000}"
BASE_URL="${BASE_URL%/}"
LOGIN_URL="${BASE_URL}/api/auth/login"

CREATOR_EMAIL="${KARARIF_CREATOR_EMAIL:-moamen.hamed33322@gmail.com}"
CREATOR_PASSWORD="${KARARIF_CREATOR_PASSWORD:-6789}"
PLAYER_EMAIL="${KARARIF_PLAYER_EMAIL:-moamen.hamed3334422@gmail.com}"
PLAYER_PASSWORD="${KARARIF_PLAYER_PASSWORD:-1234}"

login() {
  local label="$1" email="$2" password="$3"
  echo "[$label] POST $LOGIN_URL (email=$email)"
  local json
  json="$(curl -sS -X POST "$LOGIN_URL" \
    -H "Content-Type: application/json" \
    -H "Accept: application/json" \
    -d "$(jq -n --arg e "$email" --arg p "$password" '{email:$e, password:$p}')")"

  if echo "$json" | jq -e '.success == true' >/dev/null 2>&1; then
    local token
    token="$(echo "$json" | jq -r '.data.token')"
    echo "[$label] token=$token"
  else
    echo "[$label] Login failed:" >&2
    echo "$json" | jq . >&2 || echo "$json" >&2
    return 1
  fi
  echo ""
}

echo ""
login "room-creator" "$CREATOR_EMAIL" "$CREATOR_PASSWORD"
login "other-player" "$PLAYER_EMAIL" "$PLAYER_PASSWORD"
echo "Use header: Authorization: Bearer <token>"
