#!/bin/sh
set -e

cd /var/www/html

if [ -z "${APP_KEY:-}" ]; then
    if [ "${AUTO_GENERATE_APP_KEY:-true}" = "true" ]; then
        echo "APP_KEY is missing; generating a runtime key."
        export APP_KEY="$(php artisan key:generate --show --no-interaction)"
    elif [ "${APP_ENV:-local}" = "production" ]; then
        echo "APP_KEY is required in production." >&2
        exit 1
    fi
fi

if [ "${WAIT_FOR_DB:-true}" = "true" ] && [ "${DB_CONNECTION:-mysql}" = "mysql" ]; then
    echo "Waiting for MySQL..."
    until php -r "
        try {
            new PDO(
                'mysql:host=' . getenv('DB_HOST') . ';port=' . (getenv('DB_PORT') ?: 3306),
                getenv('DB_USERNAME'),
                getenv('DB_PASSWORD')
            );
            exit(0);
        } catch (Throwable \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        sleep 2
    done
    echo "MySQL is ready."
fi

if [ "${RUN_COMPOSER_INSTALL:-false}" = "true" ]; then
    echo "Running Composer install..."
    composer install --no-dev --no-interaction --no-progress --prefer-dist --optimize-autoloader
fi

php artisan storage:link --force 2>/dev/null || true

mkdir -p storage/framework/{cache,sessions,views} storage/logs bootstrap/cache
chmod -R ug+rwx storage bootstrap/cache 2>/dev/null || true

if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    echo "Running migrations..."
    php artisan migrate --force --no-interaction
fi

if [ "${RUN_SEEDERS:-false}" = "true" ]; then
    echo "Running seeders..."
    php artisan db:seed --force --no-interaction || echo "Seeders skipped or already applied."
fi

if [ "${RUN_QUEUE_WORKER:-false}" = "true" ]; then
    echo "Starting queue worker..."
    php artisan queue:work database --queue=emails-high,emails-default,emails-low,emails-bulk,emails-retry --sleep=3 --tries=3 --timeout=120 > /dev/null 2>&1 &
fi

if [ "${APP_ENV:-local}" = "production" ] && [ "${OPTIMIZE_ON_BOOT:-true}" = "true" ]; then
    php artisan package:discover --ansi 2>/dev/null || true
    php artisan optimize:clear
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
    php artisan event:cache
    php artisan filament:cache-components 2>/dev/null || true
    php artisan icons:cache 2>/dev/null || true
fi

exec "$@"
