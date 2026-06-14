# Production Deployment Guide

This guide deploys the Email Service System on an Ubuntu 22.04+ VPS with Docker Engine and Docker Compose v2.

## Server Requirements

- Ubuntu 22.04 LTS or newer
- 2 CPU cores minimum, 4 CPU cores recommended
- 2 GB RAM minimum, 4 GB+ recommended when running MySQL, Redis, queues, and scheduler on one server
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
APP_KEY=base64:replace_with_generated_key

DB_CONNECTION=mysql
DB_HOST=mysql
DB_DATABASE=email_service
DB_USERNAME=email_service
DB_PASSWORD=replace_with_strong_password

MYSQL_DATABASE=email_service
MYSQL_USER=email_service
MYSQL_PASSWORD=replace_with_same_db_password
MYSQL_ROOT_PASSWORD=replace_with_strong_root_password

REDIS_HOST=redis
REDIS_PASSWORD=replace_with_strong_redis_password

CACHE_STORE=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

MAIL_MAILER=smtp
MAIL_HOST=smtp.example.com
MAIL_PORT=587
MAIL_USERNAME=replace_me
MAIL_PASSWORD=replace_me
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@example.com
```

Generate `APP_KEY` from a PHP/Laravel environment:

```bash
php artisan key:generate --show
```

## Build And Deploy

```bash
docker compose -f docker-compose.prod.yml config
docker compose -f docker-compose.prod.yml build
docker compose -f docker-compose.prod.yml up -d mysql redis
docker compose -f docker-compose.prod.yml run --rm app php artisan migrate --force --no-interaction
docker compose -f docker-compose.prod.yml up -d app nginx queue scheduler
docker compose -f docker-compose.prod.yml ps
```

Or use the deployment helper:

```bash
chmod +x scripts/*.sh
APP_TAG="$(date +%Y%m%d%H%M%S)" ./scripts/deploy.sh
```

## Queue Startup

Queue workers are managed by Supervisor in the `queue` container:

```bash
docker compose -f docker-compose.prod.yml up -d queue
docker compose -f docker-compose.prod.yml exec queue supervisorctl status
docker compose -f docker-compose.prod.yml exec queue php artisan queue:restart
```

Tune worker counts in `.env`:

```env
QUEUE_WORKERS_HIGH=2
QUEUE_WORKERS_DEFAULT=2
QUEUE_WORKERS_LOW=1
QUEUE_WORKERS_BULK=2
QUEUE_WORKERS_RETRY=1
```

## Scheduler

The scheduler container runs `php artisan schedule:work`. No host cron entry is required.

Registered commands:

- `email:health-check` every five minutes
- `email:process-scheduled` every minute

## SSL And Reverse Proxy

For a single-app VPS, terminate TLS with a host reverse proxy such as Caddy, Traefik, Nginx Proxy Manager, or host Nginx and forward to the compose Nginx service.

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
- `redis_data`
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
docker compose -f docker-compose.prod.yml logs -f app queue scheduler nginx
docker compose -f docker-compose.prod.yml exec queue supervisorctl status
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

- Do not expose MySQL or Redis ports publicly.
- Keep `APP_DEBUG=false` in production.
- Do not rotate `APP_KEY` on an existing database unless you intentionally invalidate encrypted provider config.
- Run `php artisan queue:restart` after every deployment.
- Run seeders only for initial bootstrap or controlled test environments.
