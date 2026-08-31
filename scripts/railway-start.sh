#!/usr/bin/env bash
set -euo pipefail

php artisan config:clear
php artisan storage:link || true
# Solo esquema vacío — nunca seed en producción
php artisan migrate --force --no-interaction
php artisan config:cache
php artisan route:cache
php artisan view:cache

exec php artisan serve --host=0.0.0.0 --port="${PORT:-8000}"
