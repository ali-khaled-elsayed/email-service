#!/bin/sh
set -e

cd /var/www/html

if [ "${APP_ENV:-local}" = "production" ] && [ -z "${APP_KEY:-}" ]; then
    echo "APP_KEY is required in production." >&2
    exit 1
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

if [ "${WAIT_FOR_REDIS:-true}" = "true" ] && [ -n "${REDIS_HOST:-}" ]; then
    echo "Waiting for Redis..."
    until php -r "
        \$redis = new Redis();
        try {
            \$redis->connect(getenv('REDIS_HOST'), (int) (getenv('REDIS_PORT') ?: 6379), 2);
            \$pass = getenv('REDIS_PASSWORD');
            if (\$pass && \$pass !== 'null') { \$redis->auth(\$pass); }
            exit(\$redis->ping() ? 0 : 1);
        } catch (Throwable \$e) {
            exit(1);
        }
    " 2>/dev/null; do
        sleep 2
    done
    echo "Redis is ready."
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
