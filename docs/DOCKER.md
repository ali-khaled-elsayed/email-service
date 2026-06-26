# Docker - Production Deployment Guide

Updated production guide: `docs/DEPLOYMENT.md`.
Production readiness audit: `docs/PRODUCTION_AUDIT.md`.

The Docker stack runs Laravel on Apache, MySQL for persistence, a database queue worker, and the Laravel scheduler. Redis and Nginx are not part of the compose stack.

## Architecture

```text
Internet :80
    |
    v
app (Apache + PHP 8.3, Laravel + Filament)
    |
    +-- mysql
    +-- queue      php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry
    +-- scheduler  php artisan schedule:work
```

| Container | Role |
|-----------|------|
| `app` | Apache, PHP 8.3, Laravel, Filament admin |
| `mysql` | Primary database, database cache, database sessions, database queue |
| `queue` | Database queue worker for email priorities |
| `scheduler` | `php artisan schedule:work` |

## Quick Start

```bash
cp .env.docker.example .env
docker compose build
docker compose up -d
docker compose ps
```

The app container runs Composer during image build. On boot, `docker/entrypoint.sh` can generate `APP_KEY`, wait for MySQL, run migrations, run seeders, create the storage link, and cache Laravel config.

Important `.env` values:

```env
APP_KEY=
AUTO_GENERATE_APP_KEY=true
CACHE_STORE=database
SESSION_DRIVER=file
QUEUE_CONNECTION=database
RUN_COMPOSER_INSTALL=false
RUN_MIGRATIONS=true
RUN_SEEDERS=true
HTTP_PORT=80
```

For production, generate a stable key once and put it in `.env`:

```bash
docker compose run --rm app php artisan key:generate --show
```

## Commands

| Task | Command |
|------|---------|
| Build | `docker compose build app` |
| Start | `docker compose up -d` |
| Stop | `docker compose down` |
| Logs | `docker compose logs -f app queue scheduler` |
| Migrate | `docker compose exec app php artisan migrate --force` |
| Seed | `docker compose exec app php artisan db:seed --force` |
| Cache clear | `docker compose exec app php artisan optimize:clear` |
| Queue restart | `docker compose exec queue php artisan queue:restart` |
| Shell | `docker compose exec app bash` |
| Scale queues | `docker compose up -d --scale queue=3` |

## Queue Worker

The queue container runs:

```bash
php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry
```

The queue tables are created by the existing Laravel migrations, so Redis is not required.

## Volumes

| Volume | Data |
|--------|------|
| `mysql_data` | Database files |
| `app_storage` | `storage/app` attachments |
| `app_logs` | `storage/logs` |

## File Reference

| File | Purpose |
|------|---------|
| `docker/Dockerfile` | Multi-stage Apache production build |
| `docker/apache/000-default.conf` | Apache virtual host for Laravel public directory |
| `docker-compose.yml` | Local Docker orchestration |
| `docker-compose.prod.yml` | Production Docker orchestration |
| `docker/entrypoint.sh` | Boot: key, Composer option, DB wait, migrate, seed, cache |
| `.env.docker.example` | Docker environment template |
