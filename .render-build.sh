#!/usr/bin/env bash
set -o errexit

composer install --no-dev --optimize-autoloader
apt-get update && apt-get install -y libpq-dev
docker-php-ext-install pdo_pgsql pgsql
