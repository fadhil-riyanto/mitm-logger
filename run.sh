#!/usr/bin/env bash

set -a
source .env
set +a

mitmdump --mode regular@8081 -s hook.py