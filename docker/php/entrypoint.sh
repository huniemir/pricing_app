#!/bin/sh
set -eu

cd /var/www/html

if [ ! -f composer.json ]; then
    echo "==> Tworzenie projektu Symfony 8.1..."
    composer create-project symfony/skeleton:"8.1.*" /tmp/symfony --no-interaction

    cp -a /tmp/symfony/. /var/www/html/
    rm -rf /tmp/symfony

    echo "==> Instalowanie pakietów webapp..."
    composer require webapp --no-interaction
fi

mkdir -p var/cache var/log
chown -R www-data:www-data var

exec "$@"
