#!/bin/sh
# =============================================================================
# Prépare les dossiers de données bind-mountés (voisins du projet) avec les bons
# propriétaires. À lancer UNE FOIS sur le serveur de prod, depuis le dossier du
# projet, AVANT `docker compose -f docker-compose.prod.yml up -d`.
#
#   sh docker/prod-init-dirs.sh
#
# Permissions :
#   - PostgreSQL (image alpine) tourne en uid 70  -> ../hospital-db, ../hospital-db-backups
#   - L'app Laravel tourne en uid 1000 (user "hospital") -> ../hospital-storage, ../hospital-public
# =============================================================================
set -e

PG_UID=70
APP_UID=1000

# Dossiers voisins du projet (le script est lancé depuis la racine du projet)
DB_DIR="../hospital-db"
DB_BACKUPS_DIR="../hospital-db-backups"
STORAGE_DIR="../hospital-storage"
PUBLIC_DIR="../hospital-public"

SUDO=""
[ "$(id -u)" -ne 0 ] && SUDO="sudo"

echo "==> Création des dossiers de données voisins du projet..."
mkdir -p "$DB_DIR" "$DB_BACKUPS_DIR" "$STORAGE_DIR" "$PUBLIC_DIR"

echo "==> Attribution des droits PostgreSQL (uid $PG_UID)..."
$SUDO chown -R "$PG_UID:$PG_UID" "$DB_DIR" "$DB_BACKUPS_DIR"
$SUDO chmod 700 "$DB_DIR"

echo "==> Attribution des droits application Laravel (uid $APP_UID)..."
$SUDO chown -R "$APP_UID:$APP_UID" "$STORAGE_DIR" "$PUBLIC_DIR"

echo "==> Terminé. Dossiers prêts :"
ls -lad "$DB_DIR" "$DB_BACKUPS_DIR" "$STORAGE_DIR" "$PUBLIC_DIR"
