#!/usr/bin/env bash
set -euo pipefail

php artisan config:clear
php artisan storage:link || true
# Solo esquema vacío — nunca demo seed; sí asegurar usuario master
php artisan migrate --force --no-interaction
php artisan db:seed --class=MasterUserSeeder --force --no-interaction
php artisan db:seed --class=CatalogSeeder --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
