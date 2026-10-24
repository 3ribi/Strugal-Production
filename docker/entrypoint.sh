#!/bin/bash
set -e
cd /var/www/html

mkdir -p /data

# Le dossier storage est monté depuis l'hôte : on y recopie, sans écraser, les fichiers livrés
# avec l'image (notamment le modèle Excel storage/app/templates/departments.xlsx).
mkdir -p storage
cp -rn /opt/storage-seed/. storage/ 2>/dev/null || true
mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views storage/logs bootstrap/cache

# Clé d'application : celle de l'environnement, sinon générée une fois et conservée dans /data.
if [ -z "${APP_KEY:-}" ]; then
    if [ ! -s /data/app.key ]; then
        php artisan key:generate --show --no-interaction > /data/app.key
    fi
    export APP_KEY="$(cat /data/app.key)"
fi

export DB_CONNECTION="${DB_CONNECTION:-sqlite}"
export DB_DATABASE="${DB_DATABASE:-/data/database.sqlite}"
[ -f "$DB_DATABASE" ] || touch "$DB_DATABASE"

php artisan optimize:clear --no-interaction >/dev/null 2>&1 || true
php artisan migrate --force --no-interaction
php artisan app:install --no-interaction
php artisan optimize --no-interaction

chown -R www-data:www-data storage bootstrap/cache /data

exec "$@"
