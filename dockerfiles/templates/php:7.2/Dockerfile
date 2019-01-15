FROM php:7.2-fpm

RUN set -xe; \
        \
        apt-get update && apt-get install -y \
            libfreetype6-dev \
            libicu-dev \
            libjpeg-dev\
            libmcrypt-dev \
            libpng-dev \
            libxslt1-dev \
            ssmtp \
        ; \
        \
        docker-php-ext-configure gd \
                --with-freetype-dir=/usr/include/ \
                --with-jpeg-dir=/usr/include/ \
        ; \
        \
        docker-php-ext-install \
                bcmath \
                gd \
                hash \
                intl \
                json \
                mbstring \
                mysqli \
                opcache \
                pdo \
                pdo_mysql \
                soap \
                xsl \
                zip \
        ; \
        yes '' | pecl install redis \
        ; \
        docker-php-ext-enable redis \
        ;

$group
