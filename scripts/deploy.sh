#!/usr/bin/env bash
set -euo pipefail

COMPOSE_FILE="${COMPOSE_FILE:-docker-compose.prod.yml}"
APP_TAG="${APP_TAG:-$(date +%Y%m%d%H%M%S)}"

export APP_TAG

if [ ! -f .env ]; then
  echo ".env is missing. Copy .env.docker.example to .env and fill production values." >&2
  exit 1
fi

docker compose -f "$COMPOSE_FILE" config >/dev/null
docker compose -f "$COMPOSE_FILE" build app
docker compose -f "$COMPOSE_FILE" up -d mysql
docker compose -f "$COMPOSE_FILE" up -d --remove-orphans app queue scheduler
docker compose -f "$COMPOSE_FILE" exec queue php artisan queue:restart
docker compose -f "$COMPOSE_FILE" ps

echo "Deployment complete with APP_TAG=$APP_TAG"
