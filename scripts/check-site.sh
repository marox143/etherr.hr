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
node --check script.js

echo " - PHP syntax"
php -l api/contact-intake.php >/dev/null
php -l var/index.php >/dev/null

echo " - Shell scripts syntax"
bash -n scripts/start-localhost3000.sh
bash -n scripts/stop-localhost3000.sh
bash -n scripts/check-site.sh
bash -n scripts/smoke-contact-api.sh

echo " - Core files"
for file in index.html projekti.html about.html style.css script.js shared-header.js api/contact-intake.php .env.example README.md api/README.md; do
  if [[ ! -f "$file" ]]; then
    echo "ERROR: missing $file"
    exit 1
  fi
done

echo " - Local reference integrity"
MISSING_REFS=0
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
  grep -Eho '(src|href)="[^"]+"' index.html projekti.html about.html dfa-demo.html keef-demo.html keepgoing-demo.html juvy-demo.html reservation-calendar-demo.html reservation-schedule-demo.html \
    | sed -E 's/^[^=]+="([^"]+)"$/\1/'
)

if [[ "$MISSING_REFS" -ne 0 ]]; then
  exit 1
fi

echo "All checks passed."
