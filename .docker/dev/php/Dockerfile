ARG PHP_VERSION=8.2.4

FROM php:${PHP_VERSION}-fpm-buster

ARG COMPOSER_VERSION=2.5.7

ENV COMPOSER_MEMORY_LIMIT=-1

RUN useradd nginx && usermod -u 1001 www-data && groupmod -g 1001 www-data

RUN apt-get update && apt-get install -y nginx python3 supervisor \
     git zip bzip2 libpng-dev libpng-dev libicu-dev \
     vim libjpeg62-turbo-dev libfreetype6-dev libonig-dev \
     libpq-dev libxpm-dev libvpx-dev libbz2-dev zlib1g-dev libzip-dev gnumeric \
    && curl -sS https://getcomposer.org/installer | php -- --version="${COMPOSER_VERSION}" --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-install intl bcmath mbstring bz2 gd zip pgsql pdo_pgsql opcache pcntl sockets \
    && docker-php-ext-enable intl pcntl \
    && apt-get autoclean \
    && apt-get autoremove

COPY ./.docker/dev/php/entrypoint.sh /etc/entrypoint.sh
COPY ./.docker/dev/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./.docker/dev/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./.docker/dev/php/php-fpm.conf /etc/php/fpm/php-fpm.conf

RUN chmod +x /etc/entrypoint.sh

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www

RUN find /var/www -type d -exec chmod -R 555 {} \; \
    && find /var/www -type f -exec chmod -R 444 {} \;


RUN composer install --optimize-autoloader --prefer-dist --no-dev -o \

RUN apt-get remove -y git && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80

ENTRYPOINT ["/etc/entrypoint.sh"]
