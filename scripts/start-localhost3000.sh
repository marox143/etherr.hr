#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/.localhost3000.pid"
LOG_FILE="$ROOT_DIR/.localhost3000.log"
PORT=3000

HAS_PHP=0
HAS_PYTHON=0
if command -v php >/dev/null 2>&1; then
  HAS_PHP=1
fi
if command -v python3 >/dev/null 2>&1; then
  HAS_PYTHON=1
fi

if [[ "$HAS_PHP" -ne 1 && "$HAS_PYTHON" -ne 1 ]]; then
  echo "php or python3 is required but neither is installed."
  exit 1
fi

if lsof -iTCP:${PORT} -sTCP:LISTEN -n -P >/dev/null 2>&1; then
  echo "Port ${PORT} is already in use. Stop that process first or run scripts/stop-localhost3000.sh if it is this server."
  lsof -iTCP:${PORT} -sTCP:LISTEN -n -P
  exit 1
fi

cd "$ROOT_DIR"
mkdir -p "$(dirname "$LOG_FILE")"
: >"$LOG_FILE"

STARTED_PIDS=()

start_detached() {
  # shellcheck disable=SC2068
  if command -v setsid >/dev/null 2>&1; then
    nohup setsid $@ < /dev/null >>"$LOG_FILE" 2>&1 &
  else
    nohup $@ < /dev/null >>"$LOG_FILE" 2>&1 &
  fi
  STARTED_PIDS+=("$!")
}

SERVER_KIND=""
if [[ "$HAS_PHP" -eq 1 ]]; then
  SERVER_KIND="PHP"
  # Start both stacks so localhost works regardless of IPv4/IPv6 resolver preference.
  start_detached php -S "127.0.0.1:${PORT}" -t "$ROOT_DIR/public_html"
  start_detached php -S "[::1]:${PORT}" -t "$ROOT_DIR/public_html"
else
  SERVER_KIND="Python"
  start_detached python3 -m http.server "${PORT}" --bind localhost --directory "$ROOT_DIR/public_html"
fi

printf '%s\n' "${STARTED_PIDS[@]}" >"$PID_FILE"

sleep 0.8
RUNNING_COUNT=0
for PID in "${STARTED_PIDS[@]}"; do
  if ps -p "$PID" >/dev/null 2>&1; then
    RUNNING_COUNT=$((RUNNING_COUNT + 1))
  fi
done

if [[ "$RUNNING_COUNT" -gt 0 ]]; then
  echo "Etherr ${SERVER_KIND} server started at http://localhost:${PORT}"
  echo "Serving from: public_html/"
  if [[ "$SERVER_KIND" == "PHP" ]]; then
    echo "Bindings: 127.0.0.1:${PORT} and [::1]:${PORT}"
  else
    echo "Warning: Python fallback serves static files only. POST /api/contact-intake.php will not execute."
  fi
  echo "PID(s): ${STARTED_PIDS[*]}"
  exit 0
fi

echo "Server failed to start. Check $LOG_FILE"
exit 1
