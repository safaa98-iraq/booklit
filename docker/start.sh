#!/bin/sh
set -e

# Render's generateValue yields a raw base64 256-bit key; Laravel expects the "base64:" prefix.
case "$APP_KEY" in
  base64:*) ;;
  "") echo "APP_KEY is not set" >&2; exit 1 ;;
  *) export APP_KEY="base64:$APP_KEY" ;;
esac

php artisan migrate --force

# Optional demo entries for the owner. Idempotent.
if [ "$SEED_DEMO" = "true" ]; then
  php artisan db:seed --force
fi
php artisan optimize

chown -R www-data:www-data storage bootstrap/cache

exec apache2-foreground
