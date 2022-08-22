#!/bin/bash

service apache2 start
service php8.1-fpm start

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf