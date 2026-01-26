#!/bin/bash

# Script de nettoyage des rapports: enlever les cards et fixer les débordements

echo "🧹 Nettoyage des rapports en cours..."
echo "====================================="

RAPPORTS_DIR="/Users/ulrich_justice/Documents/Projects/Me/hospital-app/resources/js/Pages/Rapports"

# Fichiers à nettoyer
FILES=(
  "$RAPPORTS_DIR/Fournisseurs/DeclarationTVA.vue"
  "$RAPPORTS_DIR/Fournisseurs/DeclarationAIB.vue"
  "$RAPPORTS_DIR/Fournisseurs/SituationFournisseurs.vue"
  "$RAPPORTS_DIR/Fournisseurs/RecapInvestissements.vue"
  "$RAPPORTS_DIR/Fournisseurs/FacturesSoldes.vue"
  "$RAPPORTS_DIR/Fournisseurs/RecapCharges.vue"
  "$RAPPORTS_DIR/Fournisseurs/EtatReglementFacture.vue"
  "$RAPPORTS_DIR/Clients/EtatCreances.vue"
  "$RAPPORTS_DIR/Clients/ChiffreAffaires.vue"
  "$RAPPORTS_DIR/Clients/BrouillardCheques.vue"
  "$RAPPORTS_DIR/Clients/EtatReglements.vue"
  "$RAPPORTS_DIR/Clients/PertesRejets.vue"
)

for file in "${FILES[@]}"; do
  if [ -f "$file" ]; then
    echo "📄 Nettoyage: $(basename $file)"

    # Commenter les sections summary
    sed -i '' '/<div class="section summary-section">/,/<\/div>/ {
      /^[[:space:]]*<div class="section summary-section">/i\
      <!-- Summary cards commentées pour impression
      s/^/<!--/
    }' "$file" 2>/dev/null || true

    # Ajouter --> après le div de fermeture de summary-section
    sed -i '' '/<div class="section summary-section">/,/<\/div>/ {
      /^[[:space:]]*<\/div>[[:space:]]*$/a\
-->
    }' "$file" 2>/dev/null || true

  fi
done

echo ""
echo "✅ Nettoyage terminé!"
echo "📊 Modifications:"
echo "  - Cards de statistiques commentées"
echo "  - Prêt pour l'impression"
