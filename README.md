# Email Service Platform

Enterprise-grade centralized email microservice built with **Laravel 11** and **FilamentPHP v4**.

## Features

- Multi-tenant applications (`X-APP-KEY` authentication)
- Multi-provider routing (SMTP, SES, Mailgun, SendGrid, Postmark, Brevo, Resend)
- Intelligent provider resolution, failover, and health monitoring
- Database queue driver with priority queues
- Retry orchestration with exponential backoff
- Email lifecycle tracking, templates, bulk/scheduled sending
- Filament admin dashboard with analytics widgets
- REST API with OpenAPI spec and Postman collection

## Requirements

- PHP 8.2+
- MySQL 8+ (or SQLite for local dev)
- Node.js 18+ (for Vite/Filament assets)
- Composer 2

## Quick Start

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Admin panel: `http://localhost:8000/admin`  
Default user: `admin@example.com` / `password`

## API Usage

```bash
curl -X POST http://localhost:8000/api/emails/send \
  -H "X-APP-KEY: construction_app" \
  -H "Content-Type: application/json" \
  -d '{
    "to": ["user@example.com"],
    "subject": "Invoice Created",
    "html": "<h1>Hello</h1>",
    "priority": "high",
    "type": "transactional"
  }'
```

## Queue Workers

```bash
php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry
```

## Docker (Production)

Full guide: [`docs/DOCKER.md`](docs/DOCKER.md)

```bash
cp .env.docker.example .env
# Set APP_KEY, passwords, APP_URL

docker compose build
docker compose up -d

# Admin: http://localhost/admin
# API:  http://localhost/api/emails/send
```

Scale queue workers:

```bash
docker compose up -d --scale queue=3
```

Services: **app** (PHP-FPM), **nginx** (:80), **mysql**, **redis**, **queue** (Supervisor), **scheduler**.

## Architecture

```
app/Modules/EmailService/
├── Actions/          # Use-case actions
├── DTOs/             # Data transfer objects
├── Enums/            # Status/type enums
├── Services/         # Business logic layer
├── Repositories/     # Data access
├── Providers/        # Email provider adapters
├── Jobs/             # Queue jobs (email_log_id only)
├── Http/             # API controllers, middleware
└── Filament/         # Admin panel resources
```

## Configuration

See `config/email_service.php` for retry delays, queue names, rate limits, and tracking settings.

## Tests

```bash
php artisan test
```

## Documentation

- **Docker deployment:** `docs/DOCKER.md`
- OpenAPI: `docs/api/openapi.yaml`
- Postman: `docs/postman/Email-Service-API.postman_collection.json`

## License

MIT
