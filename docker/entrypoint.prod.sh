#!/bin/sh
set -e

echo "==> Initialisation du conteneur de production..."

# Supprimer tout fichier "hot" de Vite (laissé par un `npm run dev`). Sa présence force
# la directive @vite à charger les assets depuis le serveur de dev (localhost:5173), ce
# qui casse la prod (erreur CORS). On nettoie l'image ET le volume partagé persistant.
rm -f /var/www/public/hot /var/www/public-shared/hot

# Synchroniser le dossier public vers le volume partagé
if [ -d "/var/www/public" ] && [ -n "$(ls -A /var/www/public 2>/dev/null)" ]; then
    echo "==> Synchronisation du dossier public..."
    cp -r /var/www/public/. /var/www/public-shared/
fi

# Créer les répertoires storage
mkdir -p \
    /var/www/storage/app/public \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache

# Storage link
if [ ! -L "/var/www/public/storage" ]; then
    php artisan storage:link 2>/dev/null || true
fi

echo "==> Conteneur prêt."

exec php-fpm
