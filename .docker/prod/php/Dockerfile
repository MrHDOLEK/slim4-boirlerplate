ARG PHP_VERSION=8.2.4

FROM php:${PHP_VERSION}-fpm-buster

ARG COMPOSER_VERSION=2.5.7

ENV COMPOSER_MEMORY_LIMIT=-1

ARG INSTALL_COMPOSER_DEV=false
ARG INSTALL_XDEBUG=false

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

COPY ./.docker/prod/php/entrypoint.sh /etc/entrypoint.sh
COPY ./.docker/prod/nginx/nginx.conf /etc/nginx/nginx.conf
COPY ./.docker/prod/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./.docker/prod/php/php-fpm.conf /etc/php/fpm/php-fpm.conf
COPY ./.docker/prod/php/supervisord.conf /etc/supervisord.conf

RUN chmod +x /etc/entrypoint.sh

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www

RUN find /var/www -type d -exec chmod -R 555 {} \; \
    && find /var/www -type f -exec chmod -R 444 {} \;


RUN if [ ${INSTALL_COMPOSER_DEV} = true ]; then \
    composer install --optimize-autoloader \
;else \
    composer install --optimize-autoloader --prefer-dist --no-dev -o \
;fi

RUN if [ ${INSTALL_XDEBUG} = true ]; then \
    pecl install xdebug-${XDEBUG_VERSION} \
    && docker-php-ext-enable xdebug \
;fi

RUN apt-get remove -y git && apt-get autoremove -y && apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

EXPOSE 80

ENTRYPOINT ["/etc/entrypoint.sh"]
