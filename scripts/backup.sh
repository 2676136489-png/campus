#!/usr/bin/env bash
set -euo pipefail

BACKUP_DIR="${BACKUP_DIR:-./backups}"
STAMP="$(date +%Y%m%d-%H%M%S)"
mkdir -p "$BACKUP_DIR"

mysqldump -h "${DB_HOST:-127.0.0.1}" -u "${DB_USER:-root}" -p"${DB_PASS:-}" "${DB_NAME:-circle}" > "$BACKUP_DIR/db-$STAMP.sql"
tar -czf "$BACKUP_DIR/uploads-$STAMP.tar.gz" -C . uploads 2>/dev/null || true

echo "Backup written to $BACKUP_DIR (db-$STAMP.sql, uploads-$STAMP.tar.gz)"