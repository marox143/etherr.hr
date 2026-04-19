#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT_DIR"

echo "==> Etherr project checks"

if ! command -v php >/dev/null 2>&1; then
  echo "ERROR: php is required."
  exit 1
fi

if ! command -v node >/dev/null 2>&1; then
  echo "ERROR: node is required."
  exit 1
fi

echo " - JS syntax"
node --check public_html/script.js

echo " - PHP syntax"
php -l public_html/api/contact-intake.php >/dev/null
php -l var/index.php >/dev/null

echo " - Shell scripts syntax"
bash -n scripts/start-localhost3000.sh
bash -n scripts/stop-localhost3000.sh
bash -n scripts/check-site.sh
bash -n scripts/smoke-contact-api.sh

echo " - Core files in public_html/"
for file in public_html/index.html public_html/projekti.html public_html/about.html public_html/style.css public_html/script.js public_html/shared-header.js public_html/api/contact-intake.php; do
  if [[ ! -f "$file" ]]; then
    echo "ERROR: missing $file"
    exit 1
  fi
done

echo " - Sensitive files outside public_html/"
for file in .env.example README.md composer.json composer.lock; do
  if [[ ! -f "$file" ]]; then
    echo "ERROR: missing $file in project root"
    exit 1
  fi
done

echo " - Verify sensitive files NOT in public_html/"
if [[ -f "public_html/.env" ]]; then
  echo "ERROR: .env should NOT be in public_html/"
  exit 1
fi
if [[ -d "public_html/vendor" ]]; then
  echo "ERROR: vendor/ should NOT be in public_html/"
  exit 1
fi
if [[ -d "public_html/var" ]]; then
  echo "ERROR: var/ should NOT be in public_html/"
  exit 1
fi

echo " - Local reference integrity"
MISSING_REFS=0
cd "$ROOT_DIR/public_html"
while IFS= read -r REF; do
  if [[ -z "$REF" ]]; then
    continue
  fi

  if [[ "$REF" == \#* ]]; then
    continue
  fi

  case "$REF" in
    http://*|https://*|//*) 
      continue
      ;;
    /*)
      continue
      ;;
    mailto:*|tel:*|javascript:*)
      continue
      ;;
  esac

  CLEAN_REF="${REF%%\#*}"
  CLEAN_REF="${CLEAN_REF%%\?*}"
  if [[ -z "$CLEAN_REF" ]]; then
    continue
  fi

  if [[ ! -e "$CLEAN_REF" ]]; then
    echo "ERROR: missing local reference target: $REF"
    MISSING_REFS=1
  fi
done < <(
  grep -Eho '(src|href)="[^"]+"' index.html projekti.html about.html privacy.html dfa-demo.html keef-demo.html keepgoing-demo.html juvy-demo.html reservation-calendar-demo.html reservation-schedule-demo.html 2>/dev/null \
    | sed -E 's/^[^=]+="([^"]+)"$/\1/'
)

cd "$ROOT_DIR"

if [[ "$MISSING_REFS" -ne 0 ]]; then
  exit 1
fi

echo "All checks passed."
