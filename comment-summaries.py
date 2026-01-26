#!/usr/bin/env python3
import os
import re

RAPPORTS_DIR = "/Users/ulrich_justice/Documents/Projects/Me/hospital-app/resources/js/Pages/Rapports"

files_to_clean = [
    "Fournisseurs/DeclarationAIB.vue",
    "Fournisseurs/SituationFournisseurs.vue",
    "Fournisseurs/RecapInvestissements.vue",
    "Fournisseurs/FacturesSoldes.vue",
    "Fournisseurs/RecapCharges.vue",
    "Fournisseurs/EtatReglementFacture.vue",
    "Clients/EtatCreances.vue",
    "Clients/ChiffreAffaires.vue",
    "Clients/BrouillardCheques.vue",
    "Clients/EtatReglements.vue",
    "Clients/PertesRejets.vue",
]

print("🧹 Commentaire des sections summary...")
print("=" * 50)

for file_rel in files_to_clean:
    file_path = os.path.join(RAPPORTS_DIR, file_rel)

    if not os.path.exists(file_path):
        print(f"⚠️  Fichier introuvable: {file_rel}")
        continue

    print(f"📄 Traitement: {file_rel}")

    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()

    # Pattern pour trouver les sections summary
    # Cherche depuis le commentaire jusqu'au </div> de fermeture
    pattern = r'(\s*<!-- .*?summary.*?\n\s*<div class="section summary-section">.*?</div>\s*\n\s*</div>)'

    # Si la section n'est pas déjà commentée
    if '<div class="section summary-section">' in content and '<!-- ' not in content.split('<div class="section summary-section">')[0].split('\n')[-1]:
        # Trouver et commenter la section
        content = re.sub(
            r'(\s*)(<!-- .*?summary.*?\n\s*)?(<div class="section summary-section">.*?</div>\s*\n\s*</div>)',
            r'\1<!-- Summary cards commentées pour impression\n\1\3\n\1-->',
            content,
            flags=re.DOTALL
        )

    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)

print("\n✅ Terminé!")
print("📊 Toutes les sections summary ont été commentées")
