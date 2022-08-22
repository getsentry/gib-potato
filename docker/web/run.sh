#!/bin/bash

service apache2 start
service php8.1-fpm start

cd /var/www/gib-potato && bin/cake migrations migrate

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf