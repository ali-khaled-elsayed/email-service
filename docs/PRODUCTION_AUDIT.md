# Docker Production Readiness Audit

Audit date: 2026-06-08

## Score

Current Docker implementation score before remediation: **68/100**.

Expected readiness after the changes in this audit: **86/100** for a single-server VPS deployment. The remaining gap is operational: external SSL automation, off-host backups, observability, and true zero-downtime orchestration require infrastructure outside this repository.

## Reviewed Files

- `docker/Dockerfile`
- `docker-compose.yml`
- `docker-compose.prod.yml`
- `.dockerignore`
- `docker/nginx/default.conf`
- `docker/supervisor/supervisord.conf`
- `docker/supervisor/conf.d/queues.conf`
- `docker/entrypoint.sh`
- `.env.docker.example`
- Laravel queue, scheduler, logging, mail, and cache configuration

## Issues Found

| Severity | Area | Finding | Fix |
|---|---|---|---|
| High | Runtime user | PHP image ended with `USER root`, so app, queue, and scheduler processes could run with root privileges. | Changed the app image to `USER www-data` and pre-owned writable directories at build time. |
| High | Production compose | No dedicated `docker-compose.prod.yml` existed despite production deployment needs. | Added `docker-compose.prod.yml` with stricter env requirements, Redis password support, health checks, and security options. |
| High | Redis security | Redis ran without password support in compose while `.env` allowed `REDIS_PASSWORD`. | Added conditional `--requirepass` and password-aware health checks. |
| Medium | Queue config | Supervisor hard-coded `queue:work redis`, ignoring `QUEUE_CONNECTION`. | Changed queue workers to use `%(ENV_QUEUE_CONNECTION)s`. |
| Medium | Queue health | Queue health check passed if any process was running. | Changed health check to fail when any supervisor program is not `RUNNING`. |
| Medium | Queue shutdown | Supervisor workers lacked process group shutdown settings. | Added `stopasgroup=true` and `killasgroup=true`. |
| Medium | Logs | Queue logs were written inside container files instead of Docker stdout. | Changed Supervisor worker logs to `/dev/stdout`. |
| Medium | Entrypoint | Production boot did not fail fast when `APP_KEY` was missing. | Added production `APP_KEY` validation. |
| Medium | Entrypoint | Redis auth treated the literal value `null` as a password. | Added `null` guard in Redis wait logic. |
| Medium | Nginx | PHP location did not use `try_files`, so invalid PHP paths could be forwarded to FPM. | Added `try_files $uri =404`. |
| Medium | Storage | Nginx used its own copied `public/` directory and did not mount Laravel public storage. | Added a public storage symlink in the Nginx image and mounted `app_storage` read-only. |
| Low | Caching | Runtime optimization was unconditional in production and not toggleable. | Added `OPTIMIZE_ON_BOOT`. |
| Low | Static assets | Nginx did not define long-lived cache headers for built assets. | Added static asset cache policy. |
| Low | Deployment | No deployment, rollback, or backup helper scripts existed. | Added `scripts/deploy.sh`, `scripts/rollback.sh`, and `scripts/backup-mysql.sh`. |

## Risks Found

- Single VPS deployment can still have downtime during container recreation.
- Auto-running migrations on app start can be risky when multiple app containers start together.
- MySQL and Redis are containerized on the same host, so host failure affects all services.
- `.env` still contains secrets by design; production should restrict permissions to the deploy user.
- SSL certificate issuance and renewal are not handled inside this repo.
- Backups are local by default; off-host backup replication is still required.

## Required Fixes Implemented

- Added a production compose file: `docker-compose.prod.yml`.
- Changed PHP app image to non-root runtime user.
- Added production `APP_KEY` fail-fast behavior.
- Added Redis password support in compose.
- Changed Supervisor queue workers to respect `QUEUE_CONNECTION`.
- Improved queue health checks and graceful worker shutdown.
- Added deployment, backup, and rollback scripts.
- Added deployment documentation.
- Improved Nginx FastCGI and static asset handling.
- Updated `.env.docker.example` for Docker production logging, Redis, Mailtrap port `587`, and boot flags.

## Recommended Improvements

- Use an external managed database for higher availability.
- Ship backups to S3, Backblaze B2, or another off-host target.
- Put Caddy, Traefik, Nginx Proxy Manager, or a cloud load balancer in front for TLS automation.
- Add Prometheus/Grafana, Uptime Kuma, or equivalent monitoring.
- Use blue/green deployment or Docker Swarm/Kubernetes for true zero-downtime releases.
- Add image scanning in CI with Trivy or Grype.
- Add CI validation for `docker compose -f docker-compose.prod.yml config`, the test suite, and image build.

## Exact Code Changes

- `docker/Dockerfile`: added runtime utilities, pre-owned writable directories, and switched runtime user to `www-data`.
- `docker/entrypoint.sh`: added `APP_KEY` validation, wait toggles, Redis password handling, and `OPTIMIZE_ON_BOOT`.
- `docker/supervisor/supervisord.conf`: removed explicit root user directive.
- `docker/supervisor/conf.d/queues.conf`: made queue connection env-driven, added process group shutdown, and moved logs to stdout.
- `docker/nginx/default.conf`: added security/static cache headers and safer PHP forwarding.
- `docker-compose.yml`: added Redis password-aware startup and health checks, explicit queue connection, stricter queue health checks, and a read-only Nginx storage mount.
- `docker-compose.prod.yml`: added production stack for app, Nginx, MySQL, Redis, queue, and scheduler.
- `.env.docker.example`: added Docker logging, Redis password placeholder, SMTP port `587`, and boot toggles.
- `scripts/deploy.sh`: added tagged deployment helper.
- `scripts/backup-mysql.sh`: added compressed MySQL backup helper.
- `scripts/rollback.sh`: added image-tag rollback helper.
- `docs/DEPLOYMENT.md`: added VPS deployment guide.

## Production Readiness Checklist

- Laravel app container: ready.
- PHP-FPM: ready.
- Nginx reverse proxy: ready for HTTP and host-level TLS termination.
- Queue workers: ready with Supervisor.
- Scheduler: ready with `schedule:work`.
- MySQL container: ready for single-server production, not high availability.
- Redis container: ready with password support.
- Persistent volumes: present for database, Redis, storage, and logs.
- Health checks: present and improved.
- Restart policies: present.
- Cache optimization: present and toggleable.
- File permissions: improved.
- Root runtime: fixed for PHP app image.
- Backups: local script added; off-host replication still required.
