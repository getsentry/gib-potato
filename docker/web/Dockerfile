FROM ubuntu:22.04

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
    php8.1-mysql \
    php8.1-sqlite3 \
    php8.1-xml \
    php8.1-zip && \
    rm -rf /var/lib/apt/lists/*

RUN rm /etc/apache2/sites-available/*.conf
COPY apache/gib-potato.conf /etc/apache2/sites-available/gib-potato.conf

RUN \
    a2ensite gib-potato && \
    a2enmod rewrite && \
    a2enmod headers && \
    a2enmod proxy_fcgi && \
    a2enmod setenvif && \
    a2enconf php8.1-fpm && \
    sed -i 's/Options Indexes FollowSymLinks/Options FollowSymLinks/g' /etc/apache2/apache2.conf && \
    sed -i "s/AllowOverride None/AllowOverride All/g" /etc/apache2/apache2.conf

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/gib-potato

COPY run.sh /run.sh
RUN chmod +x /run.sh

EXPOSE 80

CMD [ "/bin/bash", "/run.sh" ]
