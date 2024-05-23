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

# does backup of files + db
php artisan backup:run --disable-notifications

php artisan backup:clean --disable-notifications

php artisan backup:list

git fetch

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

# not usable yet. needs testing
#php artisan config:cache
#php artisan view:cache
#php artisan route:cache

php artisan up
