#!/bin/bash
### Update StuFis to the latest release: pull, migrate and rebuild caches.
# Re-exec under bash if started via `sh update-stufis.sh`.
[ -n "${BASH_VERSION:-}" ] || exec bash "$0" "$@"
set -e

source "$(dirname -- "${BASH_SOURCE[0]}")/common.sh"

# print commands as they run
set -x

# puts StuFis in Maintenance Mode
php artisan down --with-secret

php artisan clear-compiled
php artisan optimize:clear

# does backup of files + db
php artisan backup:run --disable-notifications
php artisan backup:clean --disable-notifications
php artisan backup:list

git fetch --tags --prune

if [ -n "$1" ]; then
    # deploy a specific ref: a release tag (recommended, reproducible) or a branch
    git checkout "$1"
fi

# fast-forward only when on a branch; a tag checkout is a detached HEAD with no
# upstream to pull from, so leave it pinned at that exact release.
if git symbolic-ref -q HEAD >/dev/null; then
    git pull --ff-only
fi

# install/update the toolchain (composer.phar + nvm/node/npm)
setup_tooling

# install dependencies
composer install --no-dev --optimize-autoloader
npm ci

# update db
php artisan migrate --force

# performance optimization
php artisan view:cache
php artisan route:cache

# compile tailwind css after view cache
npm run build

php artisan up
