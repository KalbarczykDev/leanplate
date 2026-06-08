#!/usr/bin/env bash
# scripts/restore-test.sh -- restore newest backup to temp, verify integrity.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
BACKUP_DIR="$APP_DIR/backups"

LATEST="$(ls -1t "$BACKUP_DIR"/app-*.sqlite.gz 2>/dev/null | head -n1 || true)"
[ -n "$LATEST" ] || { echo "no backups found"; exit 1; }

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

gunzip -c "$LATEST" > "$TMP/restored.sqlite"

RESULT="$(sqlite3 "$TMP/restored.sqlite" "PRAGMA integrity_check;")"
echo "restore test of $LATEST: $RESULT"
[ "$RESULT" = "ok" ] || { echo "integrity check FAILED"; exit 1; }

COUNT="$(sqlite3 "$TMP/restored.sqlite" "SELECT count(*) FROM users;")"
echo "users in backup: $COUNT"
