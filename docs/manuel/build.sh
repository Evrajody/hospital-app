#!/usr/bin/env bash
# Compile tous les manuels (combiné + par profil) avec tectonic.
# Usage : ./build.sh
set -euo pipefail
cd "$(dirname "$0")"

MANUELS=(
  manuel-utilisateur            # manuel combiné, tous profils
  manuel-administrateur
  manuel-comptable
  manuel-gestionnaire
  manuel-utilisateur-simple     # profil « Utilisateur »
)

for m in "${MANUELS[@]}"; do
  echo ">> tectonic $m.tex"
  tectonic "$m.tex"
done
echo "OK — PDF générés dans $(pwd)"
