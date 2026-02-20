#!/usr/bin/env bash
set -e
cd /var/www/html
if [ -f composer.json ]; then
  if [ ! -d vendor ]; then
    composer install --no-interaction --prefer-dist
  fi
fi
php-fpm

