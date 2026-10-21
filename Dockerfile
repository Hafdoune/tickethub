FROM dunglas/frankenphp:1-php8.3 AS base

WORKDIR /app

RUN install-php-extensions pdo_mysql intl opcache zip apcu

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-app.ini
COPY docker/entrypoint.sh /usr/local/bin/app-entrypoint
RUN chmod +x /usr/local/bin/app-entrypoint

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    SERVER_NAME=:80

ENTRYPOINT ["app-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile", "--adapter", "caddyfile"]


FROM base AS dev

ENV APP_ENV=dev

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-scripts --no-interaction --prefer-dist

COPY . .
RUN composer run-script post-install-cmd


FROM base AS prod

ENV APP_ENV=prod \
    APP_DEBUG=0

COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist

COPY . .
RUN composer dump-autoload --no-dev --classmap-authoritative \
    && composer run-script --no-dev post-install-cmd \
    && php bin/console asset-map:compile \
    && chown -R www-data:www-data var
