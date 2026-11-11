#!/bin/sh

set -eu

cd /var/www/html

: "${PORT:=10000}"
export PORT

if [ -z "${APP_URL:-}" ] && [ -n "${RENDER_EXTERNAL_HOSTNAME:-}" ]; then
    export APP_URL="https://${RENDER_EXTERNAL_HOSTNAME}"
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force

envsubst '${PORT}' \
    < /etc/nginx/http.d/default.conf.template \
    > /etc/nginx/http.d/default.conf

php-fpm -D
exec nginx -g 'daemon off;'
