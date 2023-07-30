FROM php:8.2-fpm-alpine3.18

ARG APP_ENV

WORKDIR /var/www

RUN set -eux \
    && apk update \
    && apk upgrade \
    && apk add $PHPIZE_DEPS --no-cache --virtual .build-deps \
    && pecl install redis \
    && docker-php-ext-install pdo_mysql \
    && docker-php-ext-enable redis pdo_mysql \
    && apk del .build-deps

COPY . .

COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

RUN set -eux \
    && chmod -R 777 storage; \
    if [ "$APP_ENV" = "production" ]; then \
        composer install --no-dev \
        && php artisan config:cache \
        && php artisan event:cache \
        && php artisan route:cache \
        && php artisan view:cache; \
    else \
        composer install; \
    fi
