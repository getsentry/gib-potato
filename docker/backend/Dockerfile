FROM node:18-bullseye as js-build

WORKDIR /app

COPY ./frontend frontend
COPY ./templates templates
COPY ./package.json package.json
COPY ./package-lock.json package-lock.json
COPY ./postcss.config.js postcss.config.js
COPY ./tailwind.config.js tailwind.config.js
COPY ./vite.config.js vite.config.js

RUN npm install --frozen-lock
RUN npm run build

FROM ubuntu:22.04 as php-build

ENV DEBIAN_FRONTEND=noninteractive

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends \
    ca-certificates \
    software-properties-common \
    supervisor \
    apache2 \
    php8.1 \
    php8.1-curl \
    php8.1-fpm \
    php8.1-intl \
    php8.1-mbstring \
    php8.1-pgsql \
    php8.1-xml \
    php8.1-zip && \
    rm -rf /var/lib/apt/lists/*

RUN rm /etc/apache2/sites-available/*.conf
COPY ./docker/backend/apache/gib-potato.conf /etc/apache2/sites-available/gib-potato.conf

RUN \
    a2ensite gib-potato && \
    a2enconf php8.1-fpm && \
    a2enmod proxy_fcgi && \
    a2enmod rewrite && \
    sed -i "s/;clear_env = no/clear_env = no/g" /etc/php/8.1/fpm/pool.d/www.conf

RUN mkdir -p /run/php/
COPY ./docker/backend/supervisord/conf.d/ /etc/supervisor/conf.d/

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/gib-potato

COPY ./bin bin
COPY ./config config
COPY ./resources resources
COPY ./src src
COPY ./templates templates
COPY ./webroot webroot
COPY --from=js-build /app/webroot/assets webroot/assets
COPY --from=js-build /app/webroot/manifest.json webroot/manifest.json
COPY ./composer.json composer.json
COPY ./composer.lock composer.lock
COPY index.php index.php

RUN mkdir logs

RUN chmod 700 /var/www/gib-potato
RUN chown -R www-data:www-data /var/www/gib-potato

USER www-data
RUN composer install --no-dev
USER root

COPY ./docker/backend/run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 8080

CMD [ "/bin/bash", "/run.sh" ]
