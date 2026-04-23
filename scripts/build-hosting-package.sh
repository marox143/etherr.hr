#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DIST_DIR="$ROOT_DIR/dist"
PACKAGE_NAME="${1:-etherr-hosting-package.zip}"
PACKAGE_PATH="$DIST_DIR/$PACKAGE_NAME"
STAGING_DIR="$(mktemp -d "${TMPDIR:-/tmp}/etherr-hosting-package.XXXXXX")"

cleanup() {
  rm -rf "$STAGING_DIR"
}

trap cleanup EXIT

require_path() {
  local path="$1"
  if [[ ! -e "$path" ]]; then
    echo "Missing required path: $path" >&2
    exit 1
  fi
}

require_path "$ROOT_DIR/public_html"
require_path "$ROOT_DIR/vendor"
require_path "$ROOT_DIR/.env.example"
require_path "$ROOT_DIR/composer.json"
require_path "$ROOT_DIR/composer.lock"

mkdir -p "$STAGING_DIR/public_html" "$STAGING_DIR/var" "$DIST_DIR"

rsync -a \
  --delete \
  --exclude '.DS_Store' \
  "$ROOT_DIR/public_html/" \
  "$STAGING_DIR/public_html/"

rsync -a \
  --delete \
  "$ROOT_DIR/vendor/" \
  "$STAGING_DIR/vendor/"

install -m 0644 "$ROOT_DIR/.env.example" "$STAGING_DIR/.env.example"
install -m 0644 "$ROOT_DIR/composer.json" "$STAGING_DIR/composer.json"
install -m 0644 "$ROOT_DIR/composer.lock" "$STAGING_DIR/composer.lock"
: > "$STAGING_DIR/var/.gitkeep"

rm -f "$PACKAGE_PATH"
(
  cd "$STAGING_DIR"
  zip -rq "$PACKAGE_PATH" .
)

echo "Created package: $PACKAGE_PATH"
unzip -l "$PACKAGE_PATH" | sed -n '1,120p'
