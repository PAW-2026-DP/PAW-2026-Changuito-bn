# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3

# ---------------------------------------------------------------------------
# base: extensiones PHP compartidas por dev y production, sin dependencias
# de aplicación todavía. Es la capa que garantiza que todos (y el deploy)
# corren exactamente las mismas extensiones/versión de PHP.
# ---------------------------------------------------------------------------
FROM php:${PHP_VERSION}-fpm-alpine AS base

RUN apk add --no-cache \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-install \
        pdo \
        pdo_mysql \
        mbstring \
        intl \
        opcache \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock* ./

# ---------------------------------------------------------------------------
# dev: agrega Xdebug y las dependencias require-dev (phpunit incluido).
# El código fuente NO se copia acá: docker-compose lo monta como bind mount,
# así los cambios se reflejan sin rebuild.
# ---------------------------------------------------------------------------
FROM base AS dev

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS linux-headers \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && apk del .build-deps

COPY docker/php/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

RUN composer install --no-interaction --no-progress --prefer-dist

CMD ["php-fpm"]

# ---------------------------------------------------------------------------
# production: mismas extensiones que dev (heredadas de `base`), sin Xdebug
# ni require-dev, con el código copiado y el autoload optimizado. Esta es
# la imagen que se sube al contenedor de deploy.
# ---------------------------------------------------------------------------
FROM base AS production

ENV APP_ENV=production

RUN composer install --no-interaction --no-progress --prefer-dist \
        --no-dev --no-scripts --optimize-autoloader

COPY . .

RUN composer dump-autoload --optimize --no-dev --classmap-authoritative \
    && chown -R www-data:www-data /var/www/html

USER www-data

CMD ["php-fpm"]
