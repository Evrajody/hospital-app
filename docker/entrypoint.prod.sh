#!/bin/sh
set -e

echo "==> Initialisation du conteneur de production..."

# Synchroniser le dossier public vers le volume partagé avec nginx
if [ -d "/var/www/public" ]; then
    echo "==> Synchronisation du dossier public..."
    cp -r /var/www/public/. /var/www/public-shared/ 2>/dev/null || true
fi

# Créer les répertoires storage s'ils n'existent pas
mkdir -p \
    /var/www/storage/app/public \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache

# S'assurer que le storage link existe
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

echo "==> Conteneur prêt."

# Lancer PHP-FPM
exec php-fpm
