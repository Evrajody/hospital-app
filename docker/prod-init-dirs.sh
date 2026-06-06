#!/bin/sh
# =============================================================================
# Prépare les dossiers de données bind-mountés (voisins du projet) avec les bons
# propriétaires. Idempotent : le chown n'est appliqué qu'à la PREMIÈRE création
# (dossier vide), donc sûr à relancer à chaque déploiement.
#
#   sh docker/prod-init-dirs.sh        (ou : make prod-init-dirs)
#
# Permissions :
#   - PostgreSQL (image alpine) tourne en uid 70   -> ../hospital-db, ../hospital-db-backups
#   - L'app Laravel tourne en uid 1000 (user "hospital") -> ../hospital-storage, ../hospital-public
# =============================================================================
set -e

PG_UID=70
APP_UID=1000

SUDO=""
[ "$(id -u)" -ne 0 ] && SUDO="sudo"

# prepare_dir <dossier> <owner uid:gid> [mode]
prepare_dir() {
    dir="$1"
    owner="$2"
    mode="$3"
    mkdir -p "$dir"
    if [ -z "$(ls -A "$dir" 2>/dev/null)" ]; then
        $SUDO chown "$owner" "$dir"
        [ -n "$mode" ] && $SUDO chmod "$mode" "$dir"
        echo "  ✓ $dir initialisé (chown $owner${mode:+, chmod $mode})"
    else
        echo "  • $dir déjà peuplé — droits inchangés"
    fi
}

echo "==> Préparation des dossiers de données (bind mounts voisins du projet)..."
prepare_dir "../hospital-db"         "$PG_UID:$PG_UID"   "700"
prepare_dir "../hospital-db-backups" "$PG_UID:$PG_UID"
prepare_dir "../hospital-storage"    "$APP_UID:$APP_UID"
prepare_dir "../hospital-public"     "$APP_UID:$APP_UID"
echo "==> Terminé."
