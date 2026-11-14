#!/bin/sh
set -eu
cd /var/www/html

export PORT="${PORT:-10000}"
case "$PORT" in *[!0-9]*|'') echo 'PORT must be numeric.' >&2; exit 1;; esac
export APP_URL="${RENDER_EXTERNAL_URL:-${APP_URL:-http://127.0.0.1:$PORT}}"
: "${APP_KEY:?APP_KEY is required and must remain stable across deploys}"
: "${DB_URL:?DB_URL is required}"
case "$APP_KEY" in base64:*) ;; *) export APP_KEY="base64:$APP_KEY";; esac

mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views bootstrap/cache
php artisan migrate --force --no-interaction
if [ "${SIAC_SEED_DEMO:-false}" = 'true' ]; then
    php artisan db:seed --force --no-interaction
fi
php artisan config:cache
php artisan route:cache
php artisan view:cache

envsubst '${PORT}' < /etc/siac-nginx.conf.template > /tmp/siac-nginx.conf
exec supervisord -c /etc/siac-supervisord.conf
