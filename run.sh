#!/usr/bin/env bash

set -a
source .env
set +a

# php -S 0.0.0.0:12343 & -- this is deprecated now
php -S 0.0.0.0:12343 ./http/server.php &
mitmdump --mode regular@8081 -s hook.py
wait