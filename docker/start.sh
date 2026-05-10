#!/bin/sh
set -e

# Run migrations
php artisan migrate --force

# Start PHP-FPM in background
php-fpm -D

# Start nginx in foreground
nginx -g "daemon off;"
