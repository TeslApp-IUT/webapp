# syntax=docker/dockerfile:1

################################################################################
# Stage 1: Install Composer dependencies
FROM composer:lts AS deps

WORKDIR /app

COPY composer.json ./
RUN --mount=type=cache,target=/tmp/composer-cache \
    COMPOSER_CACHE_DIR=/tmp/composer-cache \
    composer install --no-dev --no-interaction

################################################################################
# Stage 2: Tailwind CSS build
FROM oven/bun:alpine AS css

WORKDIR /build
COPY package.json bun.lock ./
RUN bun install --frozen-lockfile
# Source CSS + PHP templates needed for class scanning
COPY assets/css/app.css assets/css/app.css
COPY private/Views private/Views
COPY www/index.php www/index.php
RUN bun run build

################################################################################
# Stage 3: Final runtime image (nginx + PHP-FPM on Alpine)
FROM php:8.2-fpm-alpine AS final

# Install nginx and supervisor; compile pdo_pgsql against postgresql-dev then
# drop the build-only headers to keep the image lean.
RUN apk add --no-cache libpq nginx supervisor \
    && apk add --no-cache --virtual .build-deps postgresql-dev \
    && docker-php-ext-install pdo_pgsql \
    && apk del .build-deps \
    && mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && mkdir -p /run/nginx

# Install Xdebug in local environment for debugging purposes
ARG INSTALL_XDEBUG=false
RUN if [ "$INSTALL_XDEBUG" = "true" ]; then \
      apk add --no-cache --virtual .xdebug-deps $PHPIZE_DEPS linux-headers \
      && pecl install xdebug \
      && docker-php-ext-enable xdebug \
      && apk del .xdebug-deps \
      && printf '[xdebug]\nxdebug.mode=debug\nxdebug.client_host=host.docker.internal\nxdebug.client_port=9003\nxdebug.start_with_request=yes\nxdebug.start_upon_error=yes\nxdebug.idekey=JETBRAINS\n' \
         > "$PHP_INI_DIR/conf.d/xdebug.ini"; \
    fi

COPY docker/nginx.conf /etc/nginx/nginx.conf
COPY docker/supervisord.conf /etc/supervisord.conf

# Project layout under BASE_PATH (/var/www/):
#   vendor/  — Composer autoloader (outside DocumentRoot)
#   private/ — Application source (outside DocumentRoot)
#   html/    — DocumentRoot (www/)
COPY --from=deps /app/vendor /var/www/vendor
COPY ./private /var/www/private
COPY ./www /var/www/html
# Overwrite with the minified compiled CSS from the build stage
COPY --from=css /build/www/_assets/css/styles.css /var/www/html/_assets/css/styles.css

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
