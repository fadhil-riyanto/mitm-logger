#!/usr/bin/env bash

set -a
source .env
set +a

php -S 0.0.0.0:12343 &
mitmdump --mode regular@8081 -s hook.py
wait