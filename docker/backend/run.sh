#!/bin/bash

bin/cake migrations migrate

chown -R www-data:www-data logs
chown -R www-data:www-data tmp

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
