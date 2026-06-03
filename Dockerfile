# syntax=docker/dockerfile:1

################################################################################
# Stage 1: Install Composer dependencies
FROM composer:lts AS deps

WORKDIR /app

RUN --mount=type=bind,source=composer.json,target=composer.json \
    --mount=type=bind,source=composer.lock,target=composer.lock \
    --mount=type=cache,target=/tmp/cache \
    composer install --no-dev --no-interaction

################################################################################
# Stage 2: Final runtime image (nginx + PHP-FPM on Alpine)
FROM php:8.2-fpm-alpine AS final

# Install nginx and supervisor; compile pdo_pgsql against postgresql-dev then
# drop the build-only headers to keep the image lean.
RUN apk add --no-cache libpq nginx supervisor \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del .build-deps \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && mkdir -p /run/nginx

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Project layout under BASE_PATH (/var/www/):
#   vendor/  — Composer autoloader (outside DocumentRoot)
#   private/ — Application source (outside DocumentRoot)
#   html/    — DocumentRoot (www/)
COPY --from=deps /app/vendor /var/www/vendor
COPY ./private /var/www/private
COPY ./www /var/www/html

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
