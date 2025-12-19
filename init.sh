#!/bin/bash
set -e

readonly PHP_VERSION="8.4"
readonly NODE_VERSION="22"
readonly COMPOSER_PHAR="composer.phar"
readonly NVM_DIR="$HOME/.nvm"

cd "$(dirname "$0")"

# Helper to run the specific PHP version
php_run() {
    "php$PHP_VERSION" "$@"
}

install_composer() {
    if [[ ! -f "$COMPOSER_PHAR" ]]; then
        echo "Installing Composer using PHP $PHP_VERSION..."
        php_run -r "copy('https://getcomposer.org/installer', 'composer-setup.php');"
        php_run composer-setup.php
        php_run -r "unlink('composer-setup.php');"
    fi
}

setup_nvm() {
    touch "$HOME/.profile"
    if [[ ! -d "$NVM_DIR" ]]; then
        echo "Installing NVM..."
        curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.40.3/install.sh | bash
    fi

    export NVM_DIR
    [ -s "$NVM_DIR/nvm.sh" ] && \. "$NVM_DIR/nvm.sh"

    nvm install "$NODE_VERSION"
    nvm use "$NODE_VERSION"
}

install_dependencies() {
    echo "Installing project dependencies..."
    php_run "$COMPOSER_PHAR" install
    npm ci
}

install_composer
setup_nvm
install_dependencies
