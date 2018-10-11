FROM php:7.1-fpm

ENV PHPREDIS_COMMIT 8a0ecbc162f7ffbd5251a05022947d5d0d8a3efb
ENV PHPREDIS_URL "https://github.com/phpredis/phpredis/archive/$PHPREDIS_COMMIT.tar.gz"
ENV PHPREDIS_SHA256 "bf4a85a518b1fe1fa1660843f3f49bffca1680448ce788353acebc475dbbe35e"

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
                mcrypt \
                mysqli \
                pdo \
                pdo_mysql \
                soap \
                xsl \
                zip \
        ;

RUN set -xe; \
        \
        apt-get update && apt-get install -y \
                autoconf \
                gcc \
                make \
                wget \
        ; \
        \
        mkdir -p /usr/src; \
        cd /usr/src; \
        wget -O phpredis.tar.gz "$PHPREDIS_URL"; \
        echo "$PHPREDIS_SHA256 *phpredis.tar.gz" | sha256sum -c -; \
        \
        tar xzf phpredis.tar.gz; \
        cd "/usr/src/phpredis-$PHPREDIS_COMMIT"; \
        phpize; \
        ./configure; \
        make; \
        make install; \
        docker-php-ext-enable redis; \
        cd /; \
        rm -rf /usr/src/phpredis*; \
        apt-get purge -y --auto-remove \
                autoconf \
                gcc \
                make \
                wget \
        ;

RUN set -xe \
        && groupadd -r eisenhardt-www --gid=10118 \
        && useradd -r -g eisenhardt-www --uid=10118 eisenhardt-www
