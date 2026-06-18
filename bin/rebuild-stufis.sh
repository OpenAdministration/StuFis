#!/bin/bash
### Rebuild StuFis in place: reinstall dependencies and rebuild caches/assets.
# Re-exec under bash if started via `sh rebuild-stufis.sh`.
[ -n "${BASH_VERSION:-}" ] || exec bash "$0" "$@"
set -e

source "$(dirname -- "${BASH_SOURCE[0]}")/common.sh"
load_node

# print commands as they run
set -x

# puts StuFis in Maintenance Mode
php artisan down --with-secret

php artisan clear-compiled
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
php artisan config:clear

# install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# performance optimization
php artisan view:cache
php artisan route:cache

# compile tailwind css after view cache
npm run build

php artisan up
