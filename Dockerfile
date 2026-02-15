FROM composer:lts AS build

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install \
        --no-interaction --no-progress \
        --prefer-dist \
        --no-dev \
        --no-scripts;


FROM php:8.4-fpm-alpine AS final_prod

ENV APP_ENV=prod

WORKDIR /var/www/html

## Use the default production php.ini that ships with the image
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN php-fpm -t

## This copies over the composer dependencies
COPY --from=build /app /var/www/html

## ToDo: Install PHP Extensions
# RUN

###> recipes ###
###< recipes ###

## Add application code (only what is needed at runtime)
COPY .env .env.dev importmap.php symfony.lock /var/www/html/
COPY --parents ./bin \
    ./config \
    ./public \
    ./src \
    ./templates \
    ./translations \
    /var/www/html/

RUN mkdir -p var/cache var/log \
    && chown -R www-data:www-data var
