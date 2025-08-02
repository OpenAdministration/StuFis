#!/bin/bash
### Script for updating StuFis
## Options
#stop on error
set -e
#prints commands
set -x
# change to the directory where this script lives
cd "$(dirname "$0")"

alias php="php8.4"
alias composer="php composer.phar"

if [ ! -f "composer.phar" ]; then
    php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
    php composer-setup.php
    php -r "unlink('composer-setup.php');"
fi



# puts StuFis in Maintenance Mode
php artisan down --with-secret

php artisan clear-compiled
php artisan optimize:clear

# does backup of files + db
php artisan backup:run --disable-notifications

php artisan backup:clean --disable-notifications

php artisan backup:list

git fetch

if [ -n "$1" ]
then
    git checkout "$1"
fi

git pull
# install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# update db
php artisan migrate --force

# performance optimization
#php artisan config:cache
php artisan view:cache
php artisan route:cache

# compile tailwind css after view cache
npm run build

php artisan up
