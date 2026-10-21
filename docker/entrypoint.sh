#!/bin/sh
set -e

if [ "$1" = "frankenphp" ]; then
    php bin/console doctrine:migrations:migrate --no-interaction --allow-no-migration
fi

exec "$@"
