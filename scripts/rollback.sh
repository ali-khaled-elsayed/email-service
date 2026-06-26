#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"

if [ -z "${APP_TAG:-}" ]; then
  echo "Set APP_TAG to the image tag you want to roll back to." >&2
  exit 1
fi

docker compose -f "$COMPOSE_FILE" up -d --no-build app queue scheduler
docker compose -f "$COMPOSE_FILE" exec queue php artisan queue:restart
docker compose -f "$COMPOSE_FILE" ps
