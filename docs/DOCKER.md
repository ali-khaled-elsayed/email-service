# Docker — Production Deployment Guide

Updated production guide: `docs/DEPLOYMENT.md`.
Production readiness audit: `docs/PRODUCTION_AUDIT.md`.

Complete guide for running the Email Service Platform (Laravel 11 + Filament v4) in Docker on a VPS or cloud server.

## Architecture

```text
                    ┌─────────────┐
   Internet :80 ──► │   nginx     │  static assets + reverse proxy
                    └──────┬──────┘
                           │ fastcgi :9000
                    ┌──────▼──────┐
                    │  app        │  PHP 8.3-FPM (Laravel + Filament)
                    └──────┬──────┘
                           │
              ┌────────────┼────────────┐
              │            │            │
       ┌──────▼─────┐ ┌────▼────┐ ┌────▼────────┐
       │   mysql    │ │  redis  │ │ queue       │  Supervisor → 5 queue types
       └────────────┘ └─────────┘ │ scheduler   │  schedule:work (cron)
                                  └─────────────┘
```

| Container | Role |
|-----------|------|
| **app** | PHP-FPM, Laravel, Filament admin |
| **nginx** | Reverse proxy, port 80 (SSL-ready) |
| **mysql** | Primary database |
| **redis** | Queues + cache + sessions |
| **queue** | Supervisor managing `emails-high`, `emails-default`, `emails-low`, `emails-bulk`, `emails-retry` |
| **scheduler** | `php artisan schedule:work` (health checks, scheduled emails) |

## Prerequisites

- Docker Engine 24+
- Docker Compose v2
- 2 GB+ RAM recommended (MySQL + Redis + multiple queue workers)

## Quick start

### 1. Clone and configure environment

```bash
cp .env.docker.example .env
```

Edit `.env`:

- Set **`APP_KEY`** (generate locally: `php artisan key:generate --show`)
- Set strong **`DB_PASSWORD`**, **`MYSQL_PASSWORD`**, **`MYSQL_ROOT_PASSWORD`**
- Set **`APP_URL`** to your domain (e.g. `https://mail.example.com`)
- Configure **mail** settings (SMTP or Mailtrap API keys in provider admin)

### 2. Build images

```bash
docker compose build --no-cache
```

Multi-stage build:

1. **Node** — compiles Vite/Filament frontend assets
2. **Composer** — production dependencies (`--no-dev`)
3. **PHP 8.3-FPM** — application + Supervisor configs
4. **Nginx** — static `public/` + proxy config

### 3. Start stack

```bash
docker compose up -d
```

First boot (app container):

- Waits for MySQL + Redis
- Runs `storage:link`
- Runs migrations (`RUN_MIGRATIONS=true`)
- Caches config/routes/views/Filament (production)

### 4. Verify

```bash
docker compose ps
curl http://localhost/up          # Laravel health
curl http://localhost/admin       # Filament login
```

Default admin (if `RUN_SEEDERS=true` once):

- Email: `admin@admin.com`
- Password: `admin`

### 5. Seed (first deploy only)

```bash
# In .env set RUN_SEEDERS=true, then:
docker compose up -d app

# Or one-off:
docker compose exec app php artisan db:seed --force
```

---

## Setup commands reference

| Task | Command |
|------|---------|
| Build | `docker compose build` |
| Start | `docker compose up -d` |
| Stop | `docker compose down` |
| Logs | `docker compose logs -f app queue` |
| Migrate | `docker compose exec app php artisan migrate --force` |
| Seed | `docker compose exec app php artisan db:seed --force` |
| Cache clear | `docker compose exec app php artisan optimize:clear` |
| Queue status | `docker compose exec queue supervisorctl status` |
| Shell | `docker compose exec app bash` |
| Scale queue containers | `docker compose up -d --scale queue=3` |

---

## Queue workers

Supervisor runs separate workers per queue (config: `docker/supervisor/conf.d/queues.conf`):

| Queue | Purpose | Default workers |
|-------|---------|-----------------|
| `emails-high` | Priority sends | 2 |
| `emails-default` | Standard | 2 |
| `emails-low` | Low priority | 1 |
| `emails-bulk` | Bulk campaigns | 2 |
| `emails-retry` | Failed retries | 1 |

Tune in `.env`:

```env
QUEUE_WORKERS_HIGH=2
QUEUE_WORKERS_DEFAULT=2
QUEUE_WORKERS_LOW=1
QUEUE_WORKERS_BULK=2
QUEUE_WORKERS_RETRY=1
```

### Scaling

**Option A — Scale entire queue container** (multiplies all queue types):

```bash
docker compose up -d --scale queue=3
```

**Option B — Increase workers per type** — edit `.env` worker counts and recreate:

```bash
docker compose up -d --force-recreate queue
```

---

## Scheduler

The **scheduler** container runs `php artisan schedule:work`, which executes:

- `email:health-check` — every 5 minutes
- `email:process-scheduled` — every minute

No host cron required.

---

## Environment variables

See [`.env.docker.example`](../.env.docker.example) for the full list.

| Variable | Description |
|----------|-------------|
| `APP_KEY` | Required Laravel encryption key |
| `APP_URL` | Public URL (used for links, tracking) |
| `DB_*` | MySQL connection (host=`mysql`) |
| `REDIS_*` | Redis connection (host=`redis`) |
| `QUEUE_CONNECTION` | Use `redis` in Docker |
| `RUN_MIGRATIONS` | Auto-migrate on app start (`true`/`false`) |
| `RUN_SEEDERS` | Auto-seed on app start (first deploy only) |
| `HTTP_PORT` | Host port for nginx (default `80`) |
| `QUEUE_WORKERS_*` | Supervisor worker counts |

---

## Volumes (persistent data)

| Volume | Data |
|--------|------|
| `mysql_data` | Database files |
| `redis_data` | Redis AOF |
| `app_storage` | `storage/app` (attachments) |
| `app_logs` | `storage/logs` |

Backup MySQL:

```bash
docker compose exec mysql mysqldump -u root -p email_service > backup.sql
```

---

## SSL / HTTPS

1. Obtain certificates (Let's Encrypt / certbot on host or sidecar)
2. Uncomment SSL lines in `docker/nginx/default.conf`
3. Mount certs into nginx:

```yaml
nginx:
  volumes:
    - ./docker/ssl:/etc/nginx/ssl:ro
  ports:
    - "80:80"
    - "443:443"
```

4. Set `APP_URL=https://your-domain.com`

---

## Production best practices

### Security

- Set `APP_DEBUG=false`, `APP_ENV=production`
- Use strong DB/Redis passwords
- Do not expose MySQL/Redis ports publicly (compose file omits external ports)
- Rotate `APP_KEY` only on fresh deploy (never on live DB)
- Configure firewall: allow 80/443 only

### Performance

- Images use **OPcache** with `validate_timestamps=0` (rebuild image on code changes)
- **Config/route/view caching** runs on app boot in production
- **Redis** for queue + cache reduces MySQL load
- Tune `QUEUE_WORKERS_*` based on CPU and send volume

### Deployments

1. Pull latest code
2. `docker compose build app nginx queue scheduler`
3. `docker compose up -d --force-recreate`
4. Migrations run automatically if `RUN_MIGRATIONS=true`

For zero-downtime on larger setups, use rolling updates with orchestration (Docker Swarm, Kubernetes, or blue/green).

### Monitoring

- Health: `GET /up` (Laravel built-in)
- Container healthchecks in `docker compose ps`
- Logs: `docker compose logs -f queue scheduler`
- Filament: Email Logs, Failed Attempts, Provider Health

### Filament in Docker

- Frontend assets built at **image build time** (`npm run build`)
- `php artisan filament:cache-components` runs on production boot
- Admin panel: `/admin`
- Ensure `APP_URL` matches how users access the site

---

## Troubleshooting

| Issue | Fix |
|-------|-----|
| 502 Bad Gateway | Check `docker compose logs app` — FPM may still be starting |
| Migrations fail | Verify MySQL credentials match in `.env` and `MYSQL_*` |
| Queue not processing | `docker compose logs queue`, `supervisorctl status` |
| Filament assets missing | Rebuild image (`npm run build` in Dockerfile frontend stage) |
| Permission errors | Entrypoint sets `storage` permissions; check volume mounts |
| Redis connection refused | Wait for redis healthcheck; verify `REDIS_HOST=redis` |

---

## File reference

| File | Purpose |
|------|---------|
| `docker/Dockerfile` | Multi-stage production build |
| `docker-compose.yml` | Service orchestration |
| `docker/nginx/default.conf` | Nginx reverse proxy |
| `docker/supervisor/conf.d/queues.conf` | Queue worker programs |
| `docker/entrypoint.sh` | Boot: wait DB, migrate, cache, storage:link |
| `.dockerignore` | Build context optimization |
| `.env.docker.example` | Docker environment template |






# Add Docker to PATH for this session (if needed)
$env:Path = "C:\Program Files\Docker\Docker\resources\bin;" + $env:Path

# View status
docker compose ps

# View logs
docker compose logs -f app nginx queue

# Stop everything
docker compose down

# Start again (after stop)
docker compose up -d



cd d:\emad\email-service
docker compose down -v          # removes volumes (wipes DB)
docker compose build
docker compose up -d
docker compose exec app php artisan migrate:fresh --seed --force
docker compose exec app php artisan db:seed --force
