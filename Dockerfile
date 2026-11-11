# syntax=docker/dockerfile:1

FROM node:22-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY vite.config.js ./
COPY resources ./resources
COPY public ./public
RUN npm run build


FROM composer:2 AS vendor

WORKDIR /app

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-autoloader \
    --no-scripts \
    --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --optimize --no-interaction


FROM php:8.3-fpm-alpine AS runtime

RUN apk add --no-cache \
        gettext \
        libpq \
        nginx \
    && apk add --no-cache --virtual .build-deps \
        $PHPIZE_DEPS \
        libpq-dev \
    && docker-php-ext-install -j"$(nproc)" pdo_pgsql \
    && apk del .build-deps

WORKDIR /var/www/html

COPY --from=vendor /app /var/www/html
COPY --from=frontend /app/public/build /var/www/html/public/build
COPY docker/nginx.conf.template /etc/nginx/http.d/default.conf.template
COPY docker/start.sh /usr/local/bin/start-container

RUN chmod +x /usr/local/bin/start-container \
    && mkdir -p \
        bootstrap/cache \
        storage/framework/cache/data \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
    && chown -R www-data:www-data bootstrap/cache storage

ENV APP_ENV=production \
    APP_DEBUG=false \
    LOG_CHANNEL=stderr \
    PORT=10000

EXPOSE 10000

CMD ["/usr/local/bin/start-container"]
