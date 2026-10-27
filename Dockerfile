FROM php:8.2-cli

RUN apt-get update && apt-get install -y --no-install-recommends \
        unzip \
        git \
        libsqlite3-dev \
    && docker-php-ext-install pdo_sqlite \
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

# Xdebug is configured at runtime through XDEBUG_MODE / XDEBUG_CONFIG (see docker-compose.yml).
RUN { \
        echo 'xdebug.start_with_request=trigger'; \
        echo 'xdebug.client_host=host.docker.internal'; \
        echo 'xdebug.client_port=9003'; \
    } > /usr/local/etc/php/conf.d/xdebug.ini

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY . .

RUN chmod +x docker/entrypoint.sh

ENV APP_ENV=dev

EXPOSE 8000

ENTRYPOINT ["docker/entrypoint.sh"]
CMD ["php", "-S", "0.0.0.0:8000", "-t", "public"]
