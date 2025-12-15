# Guide d'utilisation du Plan Comptable OHADA

## Description

Le Plan Comptable OHADA (Organisation pour l'Harmonisation en Afrique du Droit des Affaires) est le référentiel comptable harmonisé utilisé dans 17 pays d'Afrique francophone.

## Fichier SQL

**Fichier** : `plan_comptable_ohada.sql`

Ce fichier contient le plan comptable OHADA complet avec :
- **9 classes** de comptes (1 à 9)
- **Plusieurs niveaux hiérarchiques** (niveau 1 à 5)
- **800+ comptes** détaillés avec leur libellé

## Structure de la table

```sql
plan_comptable_ohada (
    id                  SERIAL PRIMARY KEY,
    numero_compte       VARCHAR(10) NOT NULL UNIQUE,
    libelle             VARCHAR(500) NOT NULL,
    classe              INTEGER (1-9),
    niveau              INTEGER (1-5),
    type_compte         VARCHAR(20),  -- ACTIF, PASSIF, CHARGE, PRODUIT, SPECIAL
    utilisable          BOOLEAN DEFAULT TRUE,
    created_at          TIMESTAMP,
    updated_at          TIMESTAMP
)
```

## Les 9 Classes du plan OHADA

### Classe 1 : Comptes de ressources durables (CAPITAUX)
- 10 : Capital
- 11 : Réserves
- 12 : Report à nouveau
- 13 : Résultat net
- 14 : Subventions d'investissement
- 15 : Provisions réglementées
- 16 : Emprunts et dettes assimilées
- 17 : Dettes de crédit-bail
- 18 : Dettes liées à des participations
- 19 : Provisions financières

### Classe 2 : Comptes d'actif immobilisé
- 20 : Charges immobilisées
- 21 : Immobilisations incorporelles
- 22 : Terrains
- 23 : Bâtiments
- 24 : Matériel
- 25 : Avances et acomptes sur immobilisations
- 26 : Titres de participation
- 27 : Autres immobilisations financières
- 28 : Amortissements
- 29 : Provisions pour dépréciation

### Classe 3 : Comptes de stocks
- 31 : Marchandises
- 32 : Matières premières
- 33 : Autres approvisionnements
- 34 : Produits en cours
- 35 : Services en cours
- 36 : Produits finis
- 37 : Produits intermédiaires
- 38 : Stocks en cours de route
- 39 : Dépréciation des stocks

### Classe 4 : Comptes de tiers
- 40 : Fournisseurs
- 41 : Clients
- 42 : Personnel
- 43 : Organismes sociaux
- 44 : État
- 45 : Organismes internationaux
- 46 : Associés et groupe
- 47 : Débiteurs et créditeurs divers
- 48 : Créances et dettes H.A.O.
- 49 : Dépréciation et provisions

### Classe 5 : Comptes de trésorerie
- 50 : Titres de placement
- 51 : Valeurs à encaisser
- 52 : Banques
- 53 : Établissements financiers
- 54 : Instruments de trésorerie
- 56 : Banques - Crédits
- 57 : Caisse
- 58 : Régies d'avances
- 59 : Dépréciation et provisions

### Classe 6 : Comptes de charges
- 60 : Achats
- 61 : Transports
- 62 : Services extérieurs A
- 63 : Services extérieurs B
- 64 : Impôts et taxes
- 65 : Autres charges
- 66 : Charges de personnel
- 67 : Frais financiers
- 68 : Dotations aux amortissements
- 69 : Dotations aux provisions

### Classe 7 : Comptes de produits
- 70 : Ventes
- 71 : Subventions d'exploitation
- 72 : Production immobilisée
- 73 : Variations de stocks
- 74 : Travaux et services vendus
- 75 : Autres produits
- 76 : Reprises de charges
- 77 : Revenus financiers
- 78 : Transferts de charges
- 79 : Reprises de provisions

### Classe 8 : Comptes H.A.O. (Hors Activités Ordinaires)
- 81 : Valeurs comptables des cessions d'immobilisations
- 82 : Produits des cessions d'immobilisations
- 83 : Charges H.A.O.
- 84 : Produits H.A.O.
- 85 : Dotations H.A.O.
- 86 : Reprises H.A.O.
- 87 : Participation des travailleurs
- 88 : Subventions d'équilibre
- 89 : Impôts sur le résultat

### Classe 9 : Comptes de comptabilité analytique
- 90 : Comptes réfléchis
- 91 : Reclassement des charges
- 92 : Sections analytiques
- 93 : Coûts d'achats
- 94 : Coûts de production
- 95 : Coûts de distribution
- 96 : Coûts de revient
- 97 : Différences sur niveaux d'activité
- 98 : Résultats analytiques
- 99 : Liaisons internes

## Import dans PostgreSQL

### Méthode 1 : Avec Make (recommandé)

1. Assurez-vous que les conteneurs sont démarrés :
```bash
make start
```

2. Copiez le fichier dans le conteneur et importez :
```bash
docker cp plan_comptable_ohada.sql hospital-db:/tmp/
docker exec -i hospital-db psql -U hospital_user -d hospital_db -f /tmp/plan_comptable_ohada.sql
```

### Méthode 2 : Depuis psql

```bash
# Accéder à PostgreSQL
make shell-db

# Dans psql
\i /var/www/plan_comptable_ohada.sql
```

### Méthode 3 : Import direct

```bash
psql -U hospital_user -d hospital_db -f plan_comptable_ohada.sql
```

## Requêtes utiles

### Lister tous les comptes d'une classe

```sql
-- Tous les comptes de la classe 4 (Tiers)
SELECT numero_compte, libelle, niveau, type_compte
FROM plan_comptable_ohada
WHERE classe = 4
ORDER BY numero_compte;
```

### Rechercher un compte par son libellé

```sql
-- Rechercher tous les comptes contenant "client"
SELECT numero_compte, libelle, classe, niveau
FROM plan_comptable_ohada
WHERE libelle ILIKE '%client%'
ORDER BY numero_compte;
```

### Lister les comptes de niveau 1 uniquement

```sql
SELECT numero_compte, libelle, classe, type_compte
FROM plan_comptable_ohada
WHERE niveau = 1
ORDER BY classe, numero_compte;
```

### Obtenir la hiérarchie d'un compte

```sql
-- Hiérarchie du compte 4111 (Clients)
WITH RECURSIVE hierarchy AS (
    -- Compte de départ
    SELECT numero_compte, libelle, classe, niveau
    FROM plan_comptable_ohada
    WHERE numero_compte = '4111'

    UNION

    -- Comptes parents
    SELECT p.numero_compte, p.libelle, p.classe, p.niveau
    FROM plan_comptable_ohada p
    INNER JOIN hierarchy h ON p.numero_compte = LEFT(h.numero_compte, LENGTH(h.numero_compte) - 1)
    WHERE LENGTH(p.numero_compte) < LENGTH(h.numero_compte)
)
SELECT * FROM hierarchy
ORDER BY niveau, numero_compte;
```

### Statistiques par classe

```sql
SELECT
    classe,
    CASE classe
        WHEN 1 THEN 'Ressources durables'
        WHEN 2 THEN 'Actif immobilisé'
        WHEN 3 THEN 'Stocks'
        WHEN 4 THEN 'Tiers'
        WHEN 5 THEN 'Trésorerie'
        WHEN 6 THEN 'Charges'
        WHEN 7 THEN 'Produits'
        WHEN 8 THEN 'H.A.O.'
        WHEN 9 THEN 'Analytique'
    END as nom_classe,
    COUNT(*) as nombre_comptes,
    COUNT(CASE WHEN niveau = 1 THEN 1 END) as niveau_1,
    COUNT(CASE WHEN niveau = 2 THEN 1 END) as niveau_2,
    COUNT(CASE WHEN niveau = 3 THEN 1 END) as niveau_3
FROM plan_comptable_ohada
GROUP BY classe
ORDER BY classe;
```

### Comptes de charges et produits pour un compte de résultat

```sql
-- Tous les comptes de charges (classe 6)
SELECT numero_compte, libelle
FROM plan_comptable_ohada
WHERE classe = 6 AND niveau <= 2
ORDER BY numero_compte;

-- Tous les comptes de produits (classe 7)
SELECT numero_compte, libelle
FROM plan_comptable_ohada
WHERE classe = 7 AND niveau <= 2
ORDER BY numero_compte;
```

## Intégration avec Laravel

### Créer un modèle Laravel

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompteComptable extends Model
{
    protected $table = 'plan_comptable_ohada';

    protected $fillable = [
        'numero_compte',
        'libelle',
        'classe',
        'niveau',
        'type_compte',
        'utilisable'
    ];

    protected $casts = [
        'utilisable' => 'boolean',
        'classe' => 'integer',
        'niveau' => 'integer',
    ];

    // Scope pour filtrer par classe
    public function scopeClasse($query, $classe)
    {
        return $query->where('classe', $classe);
    }

    // Scope pour filtrer par type
    public function scopeType($query, $type)
    {
        return $query->where('type_compte', $type);
    }

    // Obtenir les sous-comptes
    public function sousComptes()
    {
        return $this->hasMany(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->where('niveau', '>', $this->niveau);
    }
}
```

### Utilisation dans un contrôleur

```php
// Lister tous les comptes de tiers
$comptesTiers = CompteComptable::classe(4)->get();

// Rechercher un compte
$compte = CompteComptable::where('numero_compte', '411')->first();

// Comptes de charges
$charges = CompteComptable::type('CHARGE')->get();

// Comptes de produits
$produits = CompteComptable::type('PRODUIT')->get();
```

## Notes importantes

1. **Niveaux hiérarchiques** :
   - Niveau 1 : Comptes généraux (ex: 60)
   - Niveau 2 : Comptes principaux (ex: 601)
   - Niveau 3 : Sous-comptes (ex: 6011)
   - Niveau 4 et 5 : Détails supplémentaires

2. **Types de comptes** :
   - **ACTIF** : Comptes d'actif du bilan
   - **PASSIF** : Comptes de passif du bilan
   - **CHARGE** : Comptes de charges (classe 6)
   - **PRODUIT** : Comptes de produits (classe 7)
   - **SPECIAL** : Comptes mixtes ou analytiques

3. **Champ utilisable** :
   - Certains comptes sont des comptes de regroupement non utilisables directement
   - Généralement, seuls les comptes de niveau 3 et plus sont utilisables

## Prochaines étapes

1. Importer le plan comptable
2. Créer des migrations Laravel pour gérer les écritures comptables
3. Implémenter un système de journalisation comptable
4. Créer des vues pour le bilan et le compte de résultat
5. Développer des rapports comptables conformes OHADA

## Ressources

- [Site OHADA](http://www.ohada.org)
- [Acte uniforme relatif au droit comptable](http://www.ohada.org/index.php/fr/actes-uniformes)
