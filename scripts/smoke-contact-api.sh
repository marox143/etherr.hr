#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
API_URL="${1:-http://localhost:3000/api/contact-intake.php}"
TMP_RESPONSE="$(mktemp)"
trap 'rm -f "$TMP_RESPONSE"' EXIT

echo "==> Smoke testing contact intake endpoint"
echo "Endpoint: $API_URL"

# Verify file structure
echo " - Verifying file structure..."
if [[ ! -f "$ROOT_DIR/public_html/api/contact-intake.php" ]]; then
  echo "ERROR: API endpoint not found at public_html/api/contact-intake.php"
  exit 1
fi

if [[ ! -f "$ROOT_DIR/.env.example" ]]; then
  echo "ERROR: .env.example not found in project root"
  exit 1
fi

if [[ ! -d "$ROOT_DIR/vendor" ]] && [[ ! -f "$ROOT_DIR/composer.json" ]]; then
  echo "WARNING: vendor/ directory not found. Run 'composer install' if needed."
fi

if [[ ! -d "$ROOT_DIR/var" ]]; then
  echo "WARNING: var/ directory not found. It will be created automatically on first API call."
fi

read -r -d '' PAYLOAD <<'JSON' || true
{
  "version": "1.0",
  "submittedAt": "2026-03-27T10:30:00.000Z",
  "locale": "en",
  "source": {
    "page": "home",
    "url": "http://localhost:3000/",
    "referrer": "",
    "userAgent": "etherr-smoke-script"
  },
  "project": {
    "services": [
      { "id": "0:0", "title": "Web development", "category": "Digital" }
    ],
    "projectType": { "value": "new", "label": "New system" },
    "timeline": { "value": "month", "label": "Within one month" },
    "details": "Smoke-test submission from scripts/smoke-contact-api.sh"
  },
  "contact": {
    "company": "Smoke Test Ltd",
    "website": "https://example.com",
    "name": "Smoke Tester",
    "email": "smoke@example.com",
    "phone": "+385911234567",
    "preferredContact": { "value": "email", "label": "E-mail" }
  },
  "consent": true,
  "honeypot": "",
  "turnstileToken": ""
}
JSON

echo " - Sending test request..."

HTTP_CODE="$(
  curl -sS -o "$TMP_RESPONSE" -w "%{http_code}" \
    -H "Content-Type: application/json" \
    -H "Origin: http://localhost:3000" \
    --data "$PAYLOAD" \
    "$API_URL"
)"

RESPONSE_BODY="$(cat "$TMP_RESPONSE")"

echo "HTTP: $HTTP_CODE"
echo "Body: $RESPONSE_BODY"

if [[ "$HTTP_CODE" != "200" ]]; then
  echo "ERROR: API did not return HTTP 200"
  exit 1
fi

if grep -q '"ok":true' "$TMP_RESPONSE" && grep -Eq '"status":"(sent|queued|ignored)"' "$TMP_RESPONSE"; then
  echo "Smoke test passed."
  exit 0
fi

if grep -q '"errorCode":"TURNSTILE_REQUIRED"' "$TMP_RESPONSE"; then
  echo "WARNING: Turnstile is enforced and no token was provided."
  echo "Set TURNSTILE_ENFORCED=false for local smoke tests or provide a valid token."
  exit 2
fi

if grep -q '"errorCode":"ORIGIN_NOT_ALLOWED"' "$TMP_RESPONSE"; then
  echo "WARNING: ALLOWED_ORIGINS blocks localhost."
  echo "Include http://localhost:3000 during local testing."
  exit 2
fi

echo "ERROR: Unexpected API response."
exit 1
