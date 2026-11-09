#!/bin/sh
set -eu

export APP_SECRET="${APP_SECRET:-$(php -r 'echo bin2hex(random_bytes(32));')}"
php bin/console doctrine:migrations:migrate --no-interaction
php bin/console doctrine:fixtures:load --append --no-interaction
chown -R www-data:www-data var
exec "$@"
