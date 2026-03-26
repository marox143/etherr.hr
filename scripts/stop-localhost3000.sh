#!/usr/bin/env bash
set -euo pipefail

ROOT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
PID_FILE="$ROOT_DIR/.localhost3000.pid"
PORT=3000

if [[ -f "$PID_FILE" ]]; then
  PID="$(cat "$PID_FILE" || true)"
  if [[ -n "${PID:-}" ]] && ps -p "$PID" >/dev/null 2>&1; then
    kill "$PID" >/dev/null 2>&1 || true
    sleep 0.4
    if ps -p "$PID" >/dev/null 2>&1; then
      kill -9 "$PID" >/dev/null 2>&1 || true
    fi
    echo "Stopped server PID $PID"
  fi
  rm -f "$PID_FILE"
fi

# Fallback: if the PID file is stale but port 3000 still has a python http.server process, stop it.
SERVER_PID="$(lsof -tiTCP:${PORT} -sTCP:LISTEN -n -P 2>/dev/null | head -n 1 || true)"
if [[ -n "$SERVER_PID" ]]; then
  COMMAND_LINE="$(ps -p "$SERVER_PID" -o command= 2>/dev/null || true)"
  if [[ "$COMMAND_LINE" == *"python3 -m http.server"* ]]; then
    kill "$SERVER_PID" >/dev/null 2>&1 || true
    echo "Stopped fallback python server PID $SERVER_PID"
  fi
fi
