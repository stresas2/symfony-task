#!/bin/sh
set -e

composer install --no-interaction --prefer-dist

mkdir -p var/data

php bin/console doctrine:migrations:migrate --no-interaction

exec "$@"
