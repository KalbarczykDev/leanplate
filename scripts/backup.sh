#!/usr/bin/env bash
# scripts/backup.sh -- online SQLite backup, gzip, prune to RETAIN copies.
set -euo pipefail

APP_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
DB="$APP_DIR/data/app.sqlite"
BACKUP_DIR="$APP_DIR/backups"
RETAIN=14

mkdir -p "$BACKUP_DIR"
STAMP="$(date -u +%Y%m%d-%H%M%S)"
OUT="$BACKUP_DIR/app-$STAMP.sqlite"

# .backup is safe while the app is writing (WAL mode).
sqlite3 "$DB" ".backup '$OUT'"
gzip "$OUT"

# Keep newest RETAIN, drop the rest.
ls -1t "$BACKUP_DIR"/app-*.sqlite.gz | tail -n +$((RETAIN + 1)) | xargs -r rm -f

echo "backup ok: $OUT.gz"
