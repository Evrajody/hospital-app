#!/bin/bash

# Script de conversion des couleurs vers noir & blanc
# Ce script remplace toutes les couleurs hexadécimales par des variables CSS noir et blanc

RAPPORTS_DIR="/Users/ulrich_justice/Documents/Projects/Me/hospital-app/resources/js/Pages/Rapports"

echo "🎨 Conversion des couleurs en NOIR & BLANC..."
echo "================================================"

# Fonction de remplacement
convert_colors() {
    local file="$1"
    echo "📄 Traitement: $(basename $file)"

    # Remplacements de couleurs spécifiques vers variables
    sed -i '' \
        -e 's/#000000/var(--color-black)/g' \
        -e 's/#000/var(--color-black)/g' \
        -e 's/#1f2937/var(--color-black)/g' \
        -e 's/#111827/var(--color-black)/g' \
        -e 's/#333333/var(--color-dark)/g' \
        -e 's/#333/var(--color-dark)/g' \
        -e 's/#4b5563/var(--color-dark)/g' \
        -e 's/#6b7280/var(--color-medium)/g' \
        -e 's/#666666/var(--color-medium)/g' \
        -e 's/#666/var(--color-medium)/g' \
        -e 's/#9ca3af/var(--color-medium)/g' \
        -e 's/#999999/var(--color-medium)/g' \
        -e 's/#999/var(--color-medium)/g' \
        -e 's/#cccccc/var(--color-light)/g' \
        -e 's/#ccc/var(--color-light)/g' \
        -e 's/#d1d5db/var(--color-light)/g' \
        -e 's/#e5e7eb/var(--color-light)/g' \
        -e 's/#eeeeee/var(--color-lighter)/g' \
        -e 's/#eee/var(--color-lighter)/g' \
        -e 's/#f3f4f6/var(--color-lighter)/g' \
        -e 's/#f5f5f5/var(--color-lightest)/g' \
        -e 's/#f9fafb/var(--color-lightest)/g' \
        -e 's/#fafafa/var(--color-lightest)/g' \
        -e 's/#ffffff/var(--color-white)/g' \
        -e 's/#fff/var(--color-white)/g' \
        -e 's/white/var(--color-white)/g' \
        "$file"

    # Remplacer les couleurs non-grises (bleu, rouge, vert, jaune, etc.) par noir
    sed -i '' \
        -e 's/#2563eb/var(--color-black)/g' \
        -e 's/#1d4ed8/var(--color-black)/g' \
        -e 's/#3b82f6/var(--color-black)/g' \
        -e 's/#60a5fa/var(--color-black)/g' \
        -e 's/#ef4444/var(--color-black)/g' \
        -e 's/#dc2626/var(--color-black)/g' \
        -e 's/#f59e0b/var(--color-black)/g' \
        -e 's/#d97706/var(--color-black)/g' \
        -e 's/#f59e0b/var(--color-black)/g' \
        -e 's/#fef3c7/var(--color-lighter)/g' \
        -e 's/#78350f/var(--color-black)/g' \
        -e 's/#10b981/var(--color-black)/g' \
        -e 's/#059669/var(--color-black)/g' \
        -e 's/#22c55e/var(--color-black)/g' \
        -e 's/#16a34a/var(--color-black)/g' \
        "$file"

    # Supprimer les border-radius (pas professionnel pour documents officiels)
    sed -i '' \
        -e 's/border-radius: [0-9]*px;/border-radius: 0;/g' \
        -e 's/border-radius: [0-9\.]*rem;/border-radius: 0;/g' \
        "$file"
}

# Traiter tous les fichiers Vue dans Rapports
find "$RAPPORTS_DIR" -name "*.vue" -type f | while read file; do
    convert_colors "$file"
done

echo ""
echo "✅ Conversion terminée!"
echo "📊 Tous les rapports sont maintenant en NOIR & BLANC"
echo "💰 Économie d'encre garantie!"
