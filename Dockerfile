FROM php:8.2-fpm-alpine3.18

ARG APP_ENV

WORKDIR /var/www

RUN set -eux \
    && apk update \
    && apk upgrade
    
RUN apk add $PHPIZE_DEPS zlib-dev linux-headers  curl unzip openssl-dev --no-cache

RUN set -eux \
    && pecl install redis opentelemetry-beta grpc

RUN set -eux \
    && docker-php-ext-install pdo_mysql

RUN docker-php-ext-enable redis pdo_mysql opentelemetry grpc

COPY . .

COPY --from=composer:2.5 /usr/bin/composer /usr/bin/composer

RUN export OTEL_PHP_AUTOLOAD_ENABLED=true \
    && export OTEL_SERVICE_NAME=your-service-name \
    && export OTEL_TRACES_EXPORTER=otlp \
    && export OTEL_EXPORTER_OTLP_PROTOCOL=grpc \
    && export OTEL_EXPORTER_OTLP_ENDPOINT=http://collector:4317 \
    && export OTEL_PROPAGATORS=baggage,tracecontext

RUN set -eux \
    && chmod -R 777 storage; \
    if [ "$APP_ENV" = "local" ]; then \
        composer install; \
    else \
        composer install --no-dev \
        && php artisan config:cache \
        && php artisan event:cache \
        && php artisan route:cache \
        && php artisan view:cache; \
    fi
