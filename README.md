# Email Service Platform

Enterprise-grade centralized email microservice built with Laravel 11 and FilamentPHP v4.

## Features

- Multi-tenant application access via API keys
- Multi-provider routing for SMTP, SES, Mailgun, SendGrid, Postmark, Brevo, and Resend
- Intelligent provider resolution, failover, and health monitoring
- Database-backed queue processing with priority queues
- Retry orchestration with exponential backoff
- Email lifecycle tracking, templates, bulk sending, and scheduling
- Filament admin dashboard with analytics widgets
- REST API with OpenAPI and Postman assets

## Requirements

- PHP 8.3+
- MySQL 8+
- Node.js 20+
- Composer 2

## Local Development

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm install && npm run build
php artisan serve
```

Admin panel: http://localhost:8000/admin

## Docker

The Docker stack uses Apache for the web app, MySQL for storage, and separate services for queue and scheduler workers.

```bash only for first time 
docker compose up -d --build
docker compose exec app php artisan key:generate --show
copy app key then paste it to .env 
docker compose down -v
docker compose up -d --build
```
<!-- then only  -->
docker compose up


### What happens on startup

- The app container starts Apache
- MySQL starts and becomes healthy
- The app waits for the database before continuing
- Laravel generates the app key automatically if needed
- Composer install can run on startup if `RUN_COMPOSER_INSTALL=true`
- Laravel runs migrations
- Seeders run automatically
- The queue worker starts automatically after seeding
- Storage links and cache directories are prepared
- The app becomes available at http://localhost/admin

### Health check

The app exposes a lightweight health endpoint at:

```bash
curl http://localhost/up
```

### Default admin login

After the container starts and seeders complete, you can sign in with:

- Email: `admin@admin.com`
- Password: `admin`

### Useful Docker commands

```bash
docker compose ps
docker compose logs app
docker compose exec app php artisan db:seed
```

## API Usage

```bash
curl -X POST http://localhost/api/emails/send \
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

## Architecture

```text
app/Modules/EmailService/
├── Actions/          # Use-case actions
├── DTOs/             # Data transfer objects
├── Enums/            # Status/type enums
├── Services/         # Business logic layer
├── Repositories/     # Data access
├── Providers/        # Email provider adapters
├── Jobs/             # Queue jobs
├── Http/             # API controllers and middleware
└── Filament/         # Admin panel resources
```

## Documentation

- Docker deployment guide: [docs/DOCKER.md](docs/DOCKER.md)
- OpenAPI spec: [docs/api/openapi.yaml](docs/api/openapi.yaml)
- Postman collection: [docs/postman/Email-Service-API.postman_collection.json](docs/postman/Email-Service-API.postman_collection.json)

## License

MIT
