#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/.localhost3000.pid"
LOG_FILE="$ROOT_DIR/.localhost3000.log"
PORT=3000

if ! command -v python3 >/dev/null 2>&1; then
  echo "python3 is required but not found."
  exit 1
fi

if lsof -iTCP:${PORT} -sTCP:LISTEN -n -P >/dev/null 2>&1; then
  echo "Port ${PORT} is already in use. Stop that process first or run scripts/stop-localhost3000.sh if it is this server."
  lsof -iTCP:${PORT} -sTCP:LISTEN -n -P
  exit 1
fi

cd "$ROOT_DIR"
nohup python3 -m http.server ${PORT} --bind 127.0.0.1 >"$LOG_FILE" 2>&1 &
SERVER_PID=$!
echo "$SERVER_PID" >"$PID_FILE"

sleep 0.6
if ps -p "$SERVER_PID" >/dev/null 2>&1; then
  echo "Etherr server started at http://localhost:${PORT}"
  echo "PID: $SERVER_PID"
  exit 0
fi

echo "Server failed to start. Check $LOG_FILE"
exit 1
