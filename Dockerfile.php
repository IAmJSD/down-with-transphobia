FROM composer
WORKDIR /var/app
COPY . .
RUN composer install

FROM php:8.0-fpm
RUN apt-get update && apt-get install -y libcurl4-openssl-dev libpq-dev && docker-php-ext-install curl pgsql
COPY --from=0 /var/app /var/app
