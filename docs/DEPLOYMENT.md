# Production Deployment Guide

This guide deploys the Email Service System on an Ubuntu 22.04+ VPS with Docker Engine and Docker Compose v2.

The Docker stack now uses Apache inside the Laravel `app` container. There is no Nginx container and no Redis container. Cache, sessions, and queues use the database.

## Server Requirements

- Ubuntu 22.04 LTS or newer
- 2 CPU cores minimum, 4 CPU cores recommended
- 2 GB RAM minimum, 4 GB+ recommended when running MySQL, queues, and scheduler on one server
- 20 GB+ SSD storage
- Open inbound ports 22, 80, and 443
- A DNS record pointing your email service domain to the VPS

## Initial Server Setup

```bash
sudo apt update
sudo apt upgrade -y
sudo apt install -y ca-certificates curl git ufw
sudo ufw allow OpenSSH
sudo ufw allow 80/tcp
sudo ufw allow 443/tcp
sudo ufw enable
```

## Docker Installation

```bash
sudo install -m 0755 -d /etc/apt/keyrings
curl -fsSL https://download.docker.com/linux/ubuntu/gpg | sudo tee /etc/apt/keyrings/docker.asc >/dev/null
sudo chmod a+r /etc/apt/keyrings/docker.asc

echo "deb [arch=$(dpkg --print-architecture) signed-by=/etc/apt/keyrings/docker.asc] https://download.docker.com/linux/ubuntu $(. /etc/os-release && echo "$VERSION_CODENAME") stable" \
  | sudo tee /etc/apt/sources.list.d/docker.list >/dev/null

sudo apt update
sudo apt install -y docker-ce docker-ce-cli containerd.io docker-buildx-plugin docker-compose-plugin
sudo usermod -aG docker "$USER"
newgrp docker
docker version
docker compose version
```

## Environment Setup

```bash
git clone <repo-url> email-service
cd email-service
cp .env.docker.example .env
```

Edit `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://mail.example.com
APP_KEY=
AUTO_GENERATE_APP_KEY=true

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=email_service
DB_USERNAME=email_service
DB_PASSWORD=replace_with_strong_password

MYSQL_DATABASE=email_service
MYSQL_USER=email_service
MYSQL_PASSWORD=replace_with_same_db_password
MYSQL_ROOT_PASSWORD=replace_with_strong_root_password

CACHE_STORE=database
SESSION_DRIVER=file
QUEUE_CONNECTION=database

RUN_COMPOSER_INSTALL=false
RUN_MIGRATIONS=true
RUN_SEEDERS=true

```

`AUTO_GENERATE_APP_KEY=true` lets the container generate a runtime Laravel key if `APP_KEY` is empty. For a real production system, prefer setting a stable key once and keeping it unchanged:

```bash
docker compose -f docker-compose.prod.yml run --rm app php artisan key:generate --show
```

Put the generated `base64:...` value in `.env` as `APP_KEY`.

## Build And Deploy

```bash
docker compose -f docker-compose.prod.yml config
docker compose -f docker-compose.prod.yml build app
docker compose -f docker-compose.prod.yml up -d mysql
docker compose -f docker-compose.prod.yml up -d app queue scheduler
docker compose -f docker-compose.prod.yml ps
```

On app startup, Docker runs the Laravel boot tasks through `docker/entrypoint.sh`:

- Waits for MySQL
- Optionally generates `APP_KEY` when missing
- Optionally runs `composer install` when `RUN_COMPOSER_INSTALL=true`
- Runs `php artisan storage:link --force`
- Runs migrations when `RUN_MIGRATIONS=true`
- Runs seeders when `RUN_SEEDERS=true`
- Builds Laravel caches when `OPTIMIZE_ON_BOOT=true`

Or use the deployment helper:

```bash
chmod +x scripts/*.sh
APP_TAG="$(date +%Y%m%d%H%M%S)" ./scripts/deploy.sh
```

## Queue Startup

The `queue` container runs the database queue worker directly:

```bash
php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry
```

Operational commands:

```bash
docker compose -f docker-compose.prod.yml up -d queue
docker compose -f docker-compose.prod.yml logs -f queue
docker compose -f docker-compose.prod.yml exec queue php artisan queue:restart
```

Scale queue containers if you need more throughput:

```bash
docker compose -f docker-compose.prod.yml up -d --scale queue=3
```

## Scheduler

The scheduler container runs `php artisan schedule:work`. No host cron entry is required.

Registered commands:

- `email:health-check` every five minutes
- `email:process-scheduled` every minute

## SSL And Reverse Proxy

The compose stack exposes Apache on `${HTTP_PORT:-80}`. For HTTPS, terminate TLS with a host reverse proxy such as Caddy, Traefik, Nginx Proxy Manager, or host Apache/Nginx and forward to the compose app service.

Caddy example:

```caddyfile
mail.example.com {
    reverse_proxy 127.0.0.1:80
}
```

If the host proxy also needs port 80, set a private HTTP port:

```env
HTTP_PORT=8080
APP_URL=https://mail.example.com
```

Then proxy to `127.0.0.1:8080`.

## Backup Strategy

Create a compressed database backup:

```bash
chmod +x scripts/backup-mysql.sh
./scripts/backup-mysql.sh
```

Recommended cron:

```cron
15 2 * * * cd /opt/email-service && ./scripts/backup-mysql.sh >> backups/backup.log 2>&1
```

Also back up these volumes or replicate their data off-host:

- `mysql_data`
- `app_storage`

## Rollback Procedure

Deploy with a tag:

```bash
APP_TAG=20260608210000 ./scripts/deploy.sh
```

Rollback to a previous tag:

```bash
APP_TAG=20260607183000 ./scripts/rollback.sh
```

If the failed deploy included non-backward-compatible migrations, restore the database backup before rollback.

## Monitoring

Baseline checks:

```bash
curl -f http://127.0.0.1/up
docker compose -f docker-compose.prod.yml ps
docker compose -f docker-compose.prod.yml logs -f app queue scheduler
docker compose -f docker-compose.prod.yml exec app php artisan queue:failed
```

Recommended monitoring:

- Uptime check for `GET /up`
- Disk usage alerts for Docker volumes and backups
- Queue depth and failed job alerts
- SMTP provider health alerts from the admin dashboard
- Container restart count alerts
- Docker stdout/stderr log aggregation

## Operational Notes

- Do not expose MySQL publicly.
- Keep `APP_DEBUG=false` in production.
- Use a stable `APP_KEY` for production data; do not rotate it on an existing database unless you intentionally invalidate encrypted provider config.
- Run `php artisan queue:restart` after every deployment.
- Run seeders only for initial bootstrap or controlled test environments.
