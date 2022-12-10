FROM composer as composer

FROM php:8-cli
COPY --from=composer /usr/bin/composer /usr/bin/composer

COPY . /usr/src/golibdb
WORKDIR /usr/src/golibdb
RUN apt-get update && apt-get install -y \
    git \
    zip \
    && echo okay
 RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
 RUN composer install --ignore-platform-reqs --no-scripts
 RUN vendor/bin/phpunit test