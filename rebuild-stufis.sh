#!/bin/bash
### Script for updating StuFis
## Options
#stop on error
set -e
#prints commands
set -x
# change to the directory where this script lives
cd "$(dirname "$0")"


# puts StuFis in Maintenance Mode
php artisan down --with-secret

php artisan clear-compiled
php artisan optimize:clear

# install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# performance optimization
php artisan config:cache
php artisan view:cache
php artisan route:cache

# compile tailwind css after view cache
npm run build

php artisan up
