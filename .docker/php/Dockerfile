ARG PHP_VERSION=8.4.3
FROM php:${PHP_VERSION}-fpm-bullseye AS fpm

ARG COMPOSER_VERSION=2.8.8
ARG INSTALL_COMPOSER_DEV=false
ARG INSTALL_XDEBUG=false
ENV COMPOSER_MEMORY_LIMIT=-1

RUN usermod -u 1001 www-data && groupmod -g 1001 www-data

RUN apt-get update && apt-get install -y --no-install-recommends \
        nginx \
        python3 \
        zip \
        bzip2 \
        libpng-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libfreetype6-dev \
        libonig-dev \
        libpq-dev \
        libxpm-dev \
        libvpx-dev \
        libbz2-dev \
        zlib1g-dev \
        libzip-dev \
        gnumeric \
    && curl -sS https://getcomposer.org/installer | php -- --version="${COMPOSER_VERSION}" --install-dir=/usr/local/bin --filename=composer \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        intl \
        bcmath \
        mbstring \
        bz2 \
        gd \
        zip \
        pgsql \
        pdo_pgsql \
        opcache \
        pcntl \
        sockets \
    && docker-php-ext-enable intl pcntl \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

COPY ./.docker/nginx/default.conf /etc/nginx/conf.d/default.conf
COPY ./.docker/php/php.ini /usr/local/etc/php/conf.d/php.ini
COPY ./.docker/php/php-fpm.conf /usr/local/etc/php-fpm.d/php-fpm.conf

WORKDIR /var/www

COPY --chown=www-data:www-data . /var/www

RUN if [ ${INSTALL_COMPOSER_DEV} = true ]; then \
        composer install --optimize-autoloader; \
    else \
        composer install --optimize-autoloader --prefer-dist --no-dev -o; \
    fi

RUN if [ ${INSTALL_XDEBUG} = true ]; then \
        pecl install xdebug \
        && docker-php-ext-enable xdebug; \
    fi

EXPOSE 8080
ENTRYPOINT ["sh", "-c", "php-fpm -D && nginx -g 'daemon off;'"]