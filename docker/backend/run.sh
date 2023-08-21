#!/bin/bash

bin/cake migrations migrate

exec /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf
