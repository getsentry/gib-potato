#!/bin/bash

service apache2 start
service php8.1-fpm start

bin/cake migrations migrate

# Start supervisord and services
exec /usr/bin/supervisord  -n -c /etc/supervisor/supervisord.conf