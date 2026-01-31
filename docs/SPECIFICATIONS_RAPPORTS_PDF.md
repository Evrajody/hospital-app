# Spécifications des Rapports PDF

## 🎨 Principes de Design

### ⚫ Règle d'Or: NOIR & BLANC UNIQUEMENT
**Raison:** Économie d'encre pour les impressions fréquentes

### ✅ Ce qui est AUTORISÉ
- Noir (#000000)
- Blanc (#FFFFFF)
- Niveaux de gris (#333333, #666666, #999999, #CCCCCC, #EEEEEE)
- Texte gras/italique pour emphase
- Variations de tailles de police
- Variations de styles de bordures

### ❌ Ce qui est INTERDIT
- Couleurs (rouge, vert, bleu, jaune, etc.)
- Dégradés de couleur
- Icônes colorées
- Arrière-plans colorés
- Graphiques en couleur

---

## 📐 Variations de Styles de Bordures

### Styles disponibles pour différencier les documents

```
1. Bordure simple continue
   ┌─────────────────┐
   │                 │
   └─────────────────┘

2. Bordure double
   ╔═════════════════╗
   ║                 ║
   ╚═════════════════╝

3. Bordure épaisse
   ┏━━━━━━━━━━━━━━━━━┓
   ┃                 ┃
   ┗━━━━━━━━━━━━━━━━━┛

4. Bordure pointillée
   ┌ ─ ─ ─ ─ ─ ─ ─ ─ ┐
   ·                 ·
   └ ─ ─ ─ ─ ─ ─ ─ ─ ┘

5. Bordure mixte (haut/bas épais, côtés simples)
   ━━━━━━━━━━━━━━━━━━
   │                 │
   ━━━━━━━━━━━━━━━━━━

6. Coins arrondis
   ╭─────────────────╮
   │                 │
   ╰─────────────────╯
```

---

## 📄 Structure Standard d'un Document

### En-tête (Header)
```
═══════════════════════════════════════════════════════════════
                    HÔPITAL DE MÉNONTIN
                    [Type de Document]
═══════════════════════════════════════════════════════════════
N° Document: [XXX]                    Date: [DD/MM/YYYY]
Période: [Du XX/XX/XXXX au XX/XX/XXXX]
───────────────────────────────────────────────────────────────
```

### Corps du Document
- Tableaux avec bordures simples ou doubles
- Alternance fond blanc/gris clair pour lignes (optionnel)
- Séparateurs horizontaux pour sections

### Pied de page (Footer)
```
───────────────────────────────────────────────────────────────
Édité le: [DD/MM/YYYY à HH:MM]      Page: [X/Y]
Par: [Nom Utilisateur]              Hôpital de Ménontin
```

---

## 📋 Exemples de Styles par Type de Document

### Documents Officiels (Mandats, Déclarations)
- **Bordure:** Double épaisse
- **En-tête:** Texte centré, gras, taille 16pt
- **Tableau:** Bordures continues 1px
- **Total:** Ligne double au-dessus

### États/Rapports Périodiques
- **Bordure:** Simple continue
- **En-tête:** Texte centré, taille 14pt
- **Tableau:** Alternance lignes blanches/grises (#F5F5F5)
- **Sous-totaux:** Fond gris clair (#EEEEEE)

### Fiches d'Imputation
- **Bordure:** Épaisse en haut, simple sur côtés
- **En-tête:** Aligné à gauche, taille 12pt
- **Sections:** Séparateurs pointillés
- **Signature:** Zone avec bordure simple en bas

### Bordereaux/Relevés
- **Bordure:** Pointillée
- **En-tête:** Compact, taille 11pt
- **Tableau:** Grille complète 0.5px
- **Annotations:** Italique, gris foncé

---

## 🔤 Hiérarchie Typographique

### Tailles de Police
- **Titre principal:** 16pt, Gras
- **Sous-titre:** 14pt, Gras
- **Section:** 12pt, Gras
- **Contenu:** 10pt, Normal
- **Notes/Annotations:** 8pt, Italique
- **Pied de page:** 8pt, Normal

### Graisse (Weight)
- **Titres:** Gras (Bold)
- **Montants importants:** Gras
- **Totaux:** Gras
- **Texte standard:** Normal (Regular)
- **Remarques:** Italique

---

## 📊 Tableaux - Bonnes Pratiques

### Structure
```
┌──────────────┬──────────────┬──────────────┬──────────────┐
│ Colonne 1    │ Colonne 2    │ Colonne 3    │ Total        │
│ (Gras)       │ (Gras)       │ (Gras)       │ (Gras)       │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ Valeur 1     │ 1 000,00     │ 18%          │ 1 180,00     │
├──────────────┼──────────────┼──────────────┼──────────────┤
│ Valeur 2     │ 2 000,00     │ 18%          │ 2 360,00     │
├──────────────┼──────────────┼──────────────┼──────────────┤
│              │              │              │              │
├──────────────┴──────────────┴──────────────┼──────────────┤
│ TOTAL GÉNÉRAL (Gras)                       │ 3 540,00     │
└────────────────────────────────────────────┴──────────────┘
```

### Alternatives
- Lignes alternées: blanc / gris clair (#F9F9F9)
- Totaux: fond gris moyen (#EEEEEE) + texte gras
- Bordures internes: 0.5px
- Bordures externes: 1px

---

## 💰 Formatage des Montants

### Standards
- **Séparateur de milliers:** Espace (1 000,00)
- **Séparateur décimal:** Virgule (,)
- **Devise:** FCFA (aligné à droite)
- **Montants négatifs:** Entre parenthèses ou précédé de "-"

### Exemples
```
Montant HT:                    1 250 000,00 FCFA
TVA (18%):                       225 000,00 FCFA
                              ─────────────────────
TOTAL TTC:                     1 475 000,00 FCFA
```

---

## 🏦 Éléments Visuels Autorisés

### Séparateurs
- Ligne continue: ─────────────────
- Ligne double: ═══════════════════
- Ligne pointillée: ─ ─ ─ ─ ─ ─ ─
- Ligne épaisse: ━━━━━━━━━━━━━━━━

### Symboles en Noir & Blanc
- ☐ Case à cocher vide
- ☑ Case cochée
- • Point de liste
- ▪ Point carré
- ► Puce triangulaire
- ✓ Validation
- ✗ Rejet

### Logos/En-têtes
- Logo hôpital: NOIR & BLANC uniquement (vectoriel)
- Pas de photo couleur
- Pas de filigrane coloré

---

## 📝 Templates par Type de Document

### 1. Mandat de Paiement
- Bordure: **Double épaisse**
- Sections: Séparées par lignes simples
- Signature: 3 zones (Émetteur, Vérificateur, Approbateur)

### 2. Fiche d'Imputation Comptable
- Bordure: **Simple continue**
- Tableau imputations: Grille complète
- Zone observations: Bordure pointillée

### 3. État Périodique (Factures, Règlements)
- Bordure: **Simple fine**
- En-tête: Période en gras
- Tableau: Lignes alternées
- Totaux: Double ligne au-dessus

### 4. Bordereau de Transmission
- Bordure: **Pointillée**
- Liste numérotée
- Cases à cocher pour validation
- Zone signature en bas

### 5. Déclaration TVA/AIB
- Bordure: **Double épaisse**
- Sections numérotées
- Tableau récapitulatif en gras
- Mentions légales en petit

### 6. Situation Bancaire
- Bordure: **Épaisse en haut, simple côtés**
- Soldes: Encadrés
- Mouvements: Tableau simple
- Solde final: Fond gris + gras

---

## 🛠️ Bibliothèque PDF Recommandée

### Option 1: **Snappy/wkhtmltopdf** (RECOMMANDÉ)
**Avantages:**
- Rendu HTML/CSS → PDF parfait
- Contrôle total du design
- Support Blade templates Laravel
- Print-friendly CSS (@media print)

**Installation:**
```bash
composer require barryvdh/laravel-snappy
composer require h4cc/wkhtmltopdf-amd64
```

### Option 2: DomPDF
**Avantages:**
- Pur PHP, pas de dépendances
- Simple et léger

**Limites:**
- Support CSS limité
- Bordures complexes difficiles

### Option 3: TCPDF
**Avantages:**
- Contrôle pixel-perfect
- Support UTF-8

**Limites:**
- Code verbeux
- Pas de templating HTML

---

## 🎨 CSS Print-Friendly (pour Snappy)

```css
/* Variables de base */
:root {
  --color-black: #000000;
  --color-dark: #333333;
  --color-medium: #666666;
  --color-light: #CCCCCC;
  --color-lighter: #EEEEEE;
  --color-white: #FFFFFF;
}

/* Styles de base */
@media print {
  * {
    color: var(--color-black) !important;
    background: var(--color-white) !important;
  }

  /* Bordures */
  .border-simple { border: 1px solid var(--color-black); }
  .border-double { border: 3px double var(--color-black); }
  .border-thick { border: 2px solid var(--color-black); }
  .border-dotted { border: 1px dotted var(--color-medium); }

  /* Tableaux */
  table {
    width: 100%;
    border-collapse: collapse;
    border: 1px solid var(--color-black);
  }

  th {
    background: var(--color-lighter) !important;
    font-weight: bold;
    padding: 8px;
    border: 1px solid var(--color-black);
  }

  td {
    padding: 6px;
    border: 1px solid var(--color-light);
  }

  /* Alternance lignes */
  tr:nth-child(even) {
    background: var(--color-lighter) !important;
  }

  /* Totaux */
  .total-row {
    background: var(--color-lighter) !important;
    font-weight: bold;
    border-top: 2px solid var(--color-black);
  }

  /* Pas de saut de page dans ces éléments */
  .no-break {
    page-break-inside: avoid;
  }
}
```

---

## 📦 Structure des Templates

```
resources/
└── views/
    └── pdf/
        ├── layouts/
        │   ├── base.blade.php          # Layout de base
        │   ├── header.blade.php        # En-tête commun
        │   └── footer.blade.php        # Pied de page commun
        │
        ├── components/
        │   ├── table.blade.php         # Tableau réutilisable
        │   ├── section.blade.php       # Section avec titre
        │   └── signature.blade.php     # Zone signature
        │
        └── rapports/
            ├── fournisseurs/
            │   ├── mandat-paiement.blade.php
            │   ├── fiche-imputation.blade.php
            │   └── etat-reglements.blade.php
            │
            └── clients/
                ├── bordereau-cheques.blade.php
                └── etat-creances.blade.php
```

---

## ✅ Checklist Avant Impression

- [ ] Aucune couleur utilisée (uniquement noir, blanc, gris)
- [ ] Bordures appropriées au type de document
- [ ] Textes lisibles (taille minimale 8pt)
- [ ] Montants alignés à droite
- [ ] Totaux bien visibles (gras + bordure)
- [ ] En-tête/pied de page présents
- [ ] Numérotation des pages
- [ ] Date d'édition affichée
- [ ] Nom utilisateur affiché
- [ ] Marges suffisantes (2cm minimum)
- [ ] Test d'impression sur imprimante noir & blanc

---

## 📐 Dimensions Standards

### Format: **A4 (210 × 297 mm)**

### Marges
- Haut: 20mm
- Bas: 20mm
- Gauche: 15mm
- Droite: 15mm

### Zones
- En-tête: ~40mm
- Corps: ~217mm
- Pied de page: ~20mm

---

## 🔍 Aperçu Types de Documents

| Type | Bordure | Alternance | Signature | Niveau |
|------|---------|------------|-----------|--------|
| Mandat paiement | Double | Non | Oui (3 zones) | Officiel |
| Fiche imputation | Simple | Oui | Oui (1 zone) | Formel |
| État périodique | Simple | Oui | Non | Standard |
| Bordereau | Pointillée | Non | Oui (2 zones) | Liste |
| Déclaration fiscale | Double | Oui | Oui (2 zones) | Officiel |
| Situation bancaire | Mixte | Oui | Non | Standard |

---

**Date:** 26 Janvier 2026
**Priorité:** Économie d'encre = Noir & Blanc UNIQUEMENT
**Principe:** Simplicité professionnelle, pas de fioritures
