#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
BACKUP_DIR="${BACKUP_DIR:-backups/mysql}"
TIMESTAMP="$(date +%Y%m%d%H%M%S)"

mkdir -p "$BACKUP_DIR"

DB_NAME="$(grep -E '^MYSQL_DATABASE=' .env | cut -d= -f2-)"
DB_USER="${MYSQL_BACKUP_USER:-root}"

docker compose -f "$COMPOSE_FILE" exec -T mysql sh -c \
  "mysqldump -u \"$DB_USER\" -p\"\$MYSQL_ROOT_PASSWORD\" --single-transaction --routines --triggers \"$DB_NAME\"" \
  | gzip > "$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz"

echo "$BACKUP_DIR/${DB_NAME}_${TIMESTAMP}.sql.gz"
