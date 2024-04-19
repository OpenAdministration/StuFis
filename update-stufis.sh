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
php artisan down

php artisan backup:run

php artisan backup:clean

php artisan backup:list

if [ -n "$1" ]
then
    git checkout "$1"
else
    git pull
fi
# install dependencies
composer install --no-dev --optimize-autoloader
npm install
# compile tailwind css
npm run production
# update db
php artisan migrate --force

# not usable yet. there are some _ENV leftovers
#php artisan config:cache

php artisan route:cache
php artisan view:cache

php artisan up
