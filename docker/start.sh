#!/bin/sh
set -e

# Run migrations
php artisan migrate --force

# Keep PHP-FPM in foreground mode so its Docker stdout/stderr descriptors
# remain connected. The shell backgrounds it while nginx becomes PID 1.
php-fpm -F &

# Start nginx in foreground
exec nginx -g "daemon off;"
