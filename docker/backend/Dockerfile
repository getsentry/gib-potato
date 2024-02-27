FROM ubuntu:22.04 as php-local

ENV DEBIAN_FRONTEND=noninteractive

RUN \
    apt-get update && \
    apt-get install -y --no-install-recommends \
    software-properties-common \
    gpg-agent

RUN \
    # https://launchpad.net/~ondrej/+archive/ubuntu/php
    # ubuntu:22.04 only ships with PHP 8.1
    LC_ALL=C.UTF-8 add-apt-repository -y ppa:ondrej/php && \
    apt-get update

RUN \
    apt-get install -y --no-install-recommends \
    ca-certificates \
    supervisor \
    apache2 \
    php8.3 \
    php8.3-curl \
    php8.3-fpm \
    php8.3-intl \
    php8.3-mbstring \
    php8.3-pgsql \
    php8.3-xml \
    php8.3-zip \
    php-excimer && \
    rm -rf /var/lib/apt/lists/*

RUN rm /etc/apache2/sites-available/*.conf
COPY ./docker/backend/apache/gib-potato.conf /etc/apache2/sites-available/gib-potato.conf

RUN \
    a2ensite gib-potato && \
    a2enconf php8.3-fpm && \
    a2enmod proxy_fcgi && \
    a2enmod rewrite && \
    phpenmod -s fpm excimer && \
    # Enable zend.assertions
    # Development Value: 1
    sed -i "s/zend.assertions = -1/zend.assertions = 1/g" /etc/php/8.3/fpm/php.ini && \
    sed -i "s/exception_ignore_args = On/zend.assertions = Off/g" /etc/php/8.3/fpm/php.ini && \
    sed -i "s/;clear_env = no/clear_env = no/g" /etc/php/8.3/fpm/pool.d/www.conf && \
    sed -i "s/Listen 80/Listen 8080/g" /etc/apache2/ports.conf

RUN mkdir -p /run/php/
COPY ./docker/backend/supervisord/conf.d/ /etc/supervisor/conf.d/

WORKDIR /var/www/gib-potato

COPY ./docker/backend/run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 8080

CMD [ "/bin/bash", "/run.sh" ]

FROM node:18-bullseye as js-build

WORKDIR /app

COPY ./frontend frontend
COPY ./templates templates
COPY ./package.json package.json
COPY ./package-lock.json package-lock.json
COPY ./postcss.config.js postcss.config.js
COPY ./tailwind.config.js tailwind.config.js
COPY ./vite.config.js vite.config.js

RUN \
    npm install --frozen-lock && \
    npm run build

FROM php-local as php-build

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /app

COPY composer.json composer.lock ./

RUN composer install --no-dev

FROM php-local as php-prod

# Disable zend.assertions again
# -1: Do not compile at all
RUN sed -i "s/zend.assertions = 1/zend.assertions = -1/g" /etc/php/8.3/fpm/php.ini

WORKDIR /var/www/gib-potato

COPY ./bin bin
COPY ./config config
COPY ./resources resources
COPY ./src src
COPY ./templates templates
COPY ./webroot webroot
COPY --from=js-build /app/webroot/assets webroot/assets
COPY --from=js-build /app/webroot/.vite/manifest.json webroot/.vite/manifest.json
COPY --from=php-build /app/vendor vendor
COPY ./composer.json composer.json
COPY ./composer.lock composer.lock
COPY index.php index.php
COPY --from=getsentry/relay:nightly /bin/relay /bin/relay

RUN \
    mkdir logs && \
    chmod 700 /var/www/gib-potato && \
    chown -R www-data:www-data /var/www/gib-potato

COPY ./docker/backend/run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 8080

CMD [ "/bin/bash", "/run.sh" ]
