# Base image with PHP and extensions
FROM php:8.4.23-fpm-alpine3.24 AS symfony_php_base

# Composer base image
FROM composer/composer:2-bin AS composer_upstream

# Base project image
FROM symfony_php_base AS symfony_php

# Set working directory
WORKDIR /var/www/reservation

# Copy project files
COPY --link . ./

# Add install-php-extensions script
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install PHP build deps, PHP extensions
RUN set -eux; \
    chmod +x /usr/local/bin/install-php-extensions; \
    apk add --no-cache --virtual .build-deps \
    $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    freetype-dev \
    postgresql-dev; \
    install-php-extensions gd intl opcache zip pdo_pgsql apcu; \
    apk del .build-deps; \
    apk add --no-cache \
    acl \
    file \
    gettext \
    git \
    nodejs \
    npm; \
    mv "/var/www/reservation/docker/php/php.ini-production" "$PHP_INI_DIR/php.ini";

# Copy and set up entrypoint script
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint \
    && chmod +x bin/console

# Environment variables for composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="${PATH}:/root/.composer/vendor/bin" \
    APP_ENV=prod \
    XDEBUG_MODE=off

# Copy composer from upstream image
COPY --from=composer_upstream /composer /usr/bin/composer

# Install Symfony Flex globally
RUN set -eux; \
    composer global config --no-plugins allow-plugins.symfony/flex true; \
    composer global require "symfony/flex" --prefer-dist --no-progress --classmap-authoritative; \
    composer clear-cache

# Install PHP dependencies
RUN set -eux; \
    composer install --prefer-dist --no-dev --no-scripts --no-progress; \
    composer dump-autoload --no-dev --classmap-authoritative; \
    composer clear-cache

# Build frontend assets
RUN set -eux; \
    npm install; \
    npm run build

# Define volume
VOLUME /app/var/

# Entrypoint and command
ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]