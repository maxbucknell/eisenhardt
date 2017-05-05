FROM maxbucknell/php:7.0-console

RUN set -xe; \
        \
        apt-get update && apt-get install -y \
                autoconf \
                gcc \
                make \
        ; \
        \
        pecl install xdebug; \
        docker-php-ext-enable xdebug; \
        \
        apt-get purge -y --auto-remove \
                autoconf \
                gcc \
                make \
        ; \
        { \
                echo ""; \
                echo "xdebug.remote_enable=1"; \
                echo "xdebug.remote_handler=dbgp"; \
                echo "xdebug.remote_autostart=1"; \
                echo "xdebug.default_enable=1"; \
                echo "xdebug.remote_connect_back=1"; \
                echo "xdebug.remote_port=9000"; \
                echo "xdebug.idekey=\"docker\""; \
        } | tee -a /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini;

