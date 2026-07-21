# Base image with PHP and extensions
FROM php:8.5-fpm-alpine3.20 AS symfony_php_base

# Composer base image
FROM composer/composer:2-bin AS composer_upstream

# Base project image
FROM symfony_php_base AS symfony_php

# Set working directory
WORKDIR /var/www/intracnac

# Copy project files (excluding files listed in .dockerignore)
COPY --link . ./

# Add install-php-extensions script and install necessary extensions in one step
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN set -eux; \
    chmod +x /usr/local/bin/install-php-extensions; \
    apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    libpng-dev \
    libjpeg-turbo-dev \
    libwebp-dev \
    zlib-dev \
    freetype-dev \
    postgresql-dev; \
    install-php-extensions gd intl opcache zip pdo_pgsql apcu; \
    apk del .build-deps; \
    apk add --no-cache acl file gettext git; \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini";
RUN set -eux; \
    install-php-extensions \
    apcu \
    intl \
    opcache \
    zip \
    pdo_pgsql \
    ;
# Copy and set up entrypoint script
COPY ./docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint
RUN chmod +x /usr/local/bin/docker-entrypoint \
    && chmod +x bin/console

# Environment variables for composer
ENV COMPOSER_ALLOW_SUPERUSER=1 \
    PATH="${PATH}:/root/.composer/vendor/bin" \
    APP_ENV=prod \
    XDEBUG_MODE=off

# Copy composer from the upstream image
COPY --from=composer_upstream /composer /usr/bin/composer

# Install Symfony Flex globally and clear cache
RUN set -eux; \
    composer global config --no-plugins allow-plugins.symfony/flex true; \
    composer global require "symfony/flex" --prefer-dist --no-progress --classmap-authoritative; \
    composer clear-cache

# Install project dependencies without dev dependencies and scripts
RUN set -eux; \
    composer install --prefer-dist --no-dev --no-scripts --no-progress; \
    composer dump-autoload --no-dev --classmap-authoritative; \
    composer clear-cache

# Run Symfony console commands for production
RUN set -eux; \
    php bin/console importmap:install; \
    php bin/console tailwind:build -v; \
    php bin/console asset-map:compile --env=prod

# Define volumes
VOLUME /app/var/

# Set entrypoint and command
ENTRYPOINT ["docker-entrypoint"]
CMD ["php-fpm"]
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV PATH="${PATH}:/root/.composer/vendor/bin"
COPY --from=composer_upstream --link /composer /usr/bin/composer