FROM php:8.2-fpm-alpine3.18

WORKDIR /var/www

RUN set -eux \
    && apk update \
    && apk upgrade \
    && apk add $PHPIZE_DEPS --no-cache --virtual .build-deps \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps

COPY . .

COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

RUN set -eux \
    && composer install\
    && php artisan config:cache \
    && php artisan event:cache \
    && php artisan route:cache \
    && php artisan view:cache
