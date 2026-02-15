FROM composer:lts AS build

## Pass the environment to adjust application dependencies accordingly.
ARG APP_ENV=prod

WORKDIR /app


## Install dependencies based on the environment.
COPY composer.json composer.lock ./
RUN if [ "$APP_ENV" = "dev" ]; then \
        composer install \
            --no-interaction --no-progress \
            --prefer-dist \
            --no-scripts; \
    else \
        composer install \
            --no-interaction --no-progress \
            --prefer-dist \
            --no-dev \
            --no-scripts; \
    fi


FROM php:8.4-fpm-alpine AS final_prod

## Set APP_ENV in the image to a default value to adjust application behavior.
ARG APP_ENV=prod
ENV APP_ENV=$APP_ENV

WORKDIR /var/www/html


## Use the default production php.ini that ships with the image
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
RUN php-fpm -t


## This copies over the composer dependencies
COPY --from=build /app /var/www/html


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
