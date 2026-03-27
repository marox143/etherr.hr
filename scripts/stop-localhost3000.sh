#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/.localhost3000.pid"
PORT=3000

if [[ -f "$PID_FILE" ]]; then
  while IFS= read -r PID; do
    if [[ -z "${PID:-}" ]]; then
      continue
    fi
    if ps -p "$PID" >/dev/null 2>&1; then
      kill "$PID" >/dev/null 2>&1 || true
      sleep 0.2
      if ps -p "$PID" >/dev/null 2>&1; then
        kill -9 "$PID" >/dev/null 2>&1 || true
      fi
      echo "Stopped server PID $PID"
    fi
  done <"$PID_FILE"
  rm -f "$PID_FILE"
fi

# Fallback: if PID file is stale, stop remaining local dev listeners on this port.
while IFS= read -r SERVER_PID; do
  if [[ -z "$SERVER_PID" ]]; then
    continue
  fi
  COMMAND_LINE="$(ps -p "$SERVER_PID" -o command= 2>/dev/null || true)"
  if [[ "$COMMAND_LINE" == *"http.server"* ]] || [[ "$COMMAND_LINE" == *"php -S"*":${PORT}"* ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
    echo "Stopped fallback dev server PID $SERVER_PID"
  fi
done < <(lsof -tiTCP:${PORT} -sTCP:LISTEN -n -P 2>/dev/null || true)
