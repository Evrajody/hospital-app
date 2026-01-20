# Exemples d'utilisation du Plan Comptable OHADA

## Trouver le parent d'un compte

### Méthode 1 : Requête SQL simple

```sql
-- Parent du compte 6011
SELECT *
FROM plan_comptable_ohada
WHERE numero_compte = LEFT('6011', LENGTH('6011') - 1);
```

### Méthode 2 : Avec fonction (recommandé)

```sql
-- Après avoir importé fonctions_plan_comptable.sql
SELECT * FROM get_compte_parent('6011');
```

**Résultat :**
```
numero_compte | libelle                       | classe | niveau
601           | Achats de marchandises        | 6      | 2
```

## Obtenir toute la hiérarchie d'un compte

```sql
-- Hiérarchie complète du compte 6011
SELECT * FROM get_hierarchie_complete('6011');
```

**Résultat :**
```
numero_compte | libelle                                    | niveau | profondeur
6             | ACHATS ET VARIATIONS DE STOCKS             | 1      | 3
60            | ACHATS ET VARIATIONS DE STOCKS             | 1      | 2
601           | Achats de marchandises                     | 2      | 1
6011          | Achats de marchandises groupe A            | 3      | 0
```

## Obtenir tous les enfants d'un compte

```sql
-- Tous les sous-comptes de 60
SELECT * FROM get_comptes_enfants('60');

-- Uniquement les enfants directs
SELECT * FROM get_comptes_enfants('60', 1);
```

## Obtenir le chemin complet (breadcrumb)

```sql
SELECT get_chemin_compte('6011');
```

**Résultat :**
```
6 - ACHATS ET VARIATIONS DE STOCKS > 60 - ACHATS ET VARIATIONS DE STOCKS > 601 - Achats de marchandises > 6011 - Achats de marchandises groupe A
```

## Rechercher des comptes

```sql
-- Rechercher tous les comptes contenant "client"
SELECT * FROM rechercher_comptes('client');

-- Rechercher par numéro partiel
SELECT * FROM rechercher_comptes('411');
```

## Valider un numéro de compte

```sql
-- Vérifier si un compte existe
SELECT * FROM valider_numero_compte('6011');   -- Existe
SELECT * FROM valider_numero_compte('9999');   -- N'existe pas
```

## Vue avec parents

```sql
-- Voir tous les comptes avec leurs parents
SELECT
    numero_compte,
    libelle,
    numero_compte_parent,
    libelle_parent
FROM v_comptes_avec_parents
WHERE numero_compte LIKE '60%'
ORDER BY numero_compte;
```

## Exemples pratiques

### 1. Afficher un compte et son parent

```sql
SELECT
    c.numero_compte as compte,
    c.libelle as libelle_compte,
    p.numero_compte as parent,
    p.libelle as libelle_parent
FROM plan_comptable_ohada c
LEFT JOIN plan_comptable_ohada p
    ON p.numero_compte = LEFT(c.numero_compte, LENGTH(c.numero_compte) - 1)
WHERE c.numero_compte = '4111';
```

### 2. Lister tous les comptes de niveau 3 avec leurs parents

```sql
SELECT
    c.numero_compte,
    c.libelle,
    get_chemin_compte(c.numero_compte) as chemin
FROM plan_comptable_ohada c
WHERE c.niveau = 3
ORDER BY c.numero_compte
LIMIT 20;
```

### 3. Trouver tous les comptes "Clients"

```sql
SELECT
    numero_compte,
    libelle,
    niveau,
    get_chemin_compte(numero_compte) as hierarchie
FROM plan_comptable_ohada
WHERE numero_compte LIKE '41%'
ORDER BY numero_compte;
```

### 4. Comptes utilisables directement (feuilles de l'arbre)

```sql
-- Comptes qui n'ont pas d'enfants (comptes terminaux)
SELECT c1.numero_compte, c1.libelle
FROM plan_comptable_ohada c1
WHERE NOT EXISTS (
    SELECT 1
    FROM plan_comptable_ohada c2
    WHERE c2.numero_compte LIKE c1.numero_compte || '%'
      AND LENGTH(c2.numero_compte) > LENGTH(c1.numero_compte)
)
AND c1.classe = 4  -- Tiers par exemple
ORDER BY c1.numero_compte;
```

### 5. Arbre comptable d'une classe

```sql
WITH RECURSIVE arbre AS (
    -- Racine (niveau 1)
    SELECT
        numero_compte,
        libelle,
        niveau,
        numero_compte as path,
        0 as depth
    FROM plan_comptable_ohada
    WHERE classe = 6 AND niveau = 1

    UNION ALL

    -- Enfants
    SELECT
        c.numero_compte,
        c.libelle,
        c.niveau,
        a.path || ' > ' || c.numero_compte,
        a.depth + 1
    FROM plan_comptable_ohada c
    INNER JOIN arbre a ON c.numero_compte LIKE a.numero_compte || '%'
        AND LENGTH(c.numero_compte) = LENGTH(a.numero_compte) + 1
)
SELECT
    REPEAT('  ', depth) || numero_compte as arbre,
    libelle
FROM arbre
ORDER BY path;
```

## Import des fonctions

```bash
# Méthode 1 : Avec Make
make start
docker cp fonctions_plan_comptable.sql hospital-db:/tmp/
docker exec -i hospital-db psql -U hospital_user -d hospital_db -f /tmp/fonctions_plan_comptable.sql

# Méthode 2 : Depuis psql
make shell-db
\i /var/www/fonctions_plan_comptable.sql
```

## Utilisation dans Laravel

### Modèle CompteComptable avec relations

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

    // Relation : Parent du compte
    public function parent()
    {
        if (strlen($this->numero_compte) <= 1) {
            return null;
        }

        $numeroParent = substr($this->numero_compte, 0, -1);

        return $this->belongsTo(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', $numeroParent);
    }

    // Relation : Tous les enfants directs
    public function enfants()
    {
        return $this->hasMany(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', 'LIKE', $this->numero_compte . '_')
            ->whereRaw('LENGTH(numero_compte) = ?', [strlen($this->numero_compte) + 1]);
    }

    // Relation : Tous les descendants
    public function descendants()
    {
        return $this->hasMany(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->where('numero_compte', '!=', $this->numero_compte);
    }

    // Obtenir la hiérarchie complète
    public function getHierarchieAttribute()
    {
        $hierarchie = collect([$this]);
        $compte = $this;

        while ($parent = $compte->parent) {
            $hierarchie->prepend($parent);
            $compte = $parent;
        }

        return $hierarchie;
    }

    // Obtenir le chemin (breadcrumb)
    public function getCheminAttribute()
    {
        return $this->hierarchie->map(function ($compte) {
            return $compte->numero_compte . ' - ' . $compte->libelle;
        })->implode(' > ');
    }

    // Vérifier si c'est un compte feuille (utilisable)
    public function getEstFeuilleAttribute()
    {
        return $this->enfants()->count() === 0;
    }

    // Scope : Par classe
    public function scopeClasse($query, $classe)
    {
        return $query->where('classe', $classe);
    }

    // Scope : Par niveau
    public function scopeNiveau($query, $niveau)
    {
        return $query->where('niveau', $niveau);
    }

    // Scope : Comptes utilisables (feuilles)
    public function scopeUtilisables($query)
    {
        return $query->whereRaw('NOT EXISTS (
            SELECT 1 FROM plan_comptable_ohada c2
            WHERE c2.numero_compte LIKE CONCAT(plan_comptable_ohada.numero_compte, \'%\')
            AND LENGTH(c2.numero_compte) > LENGTH(plan_comptable_ohada.numero_compte)
        )');
    }
}
```

### Exemples d'utilisation dans un contrôleur

```php
<?php

namespace App\Http\Controllers;

use App\Models\CompteComptable;

class ComptableController extends Controller
{
    // Obtenir un compte avec son parent
    public function show($numeroCompte)
    {
        $compte = CompteComptable::where('numero_compte', $numeroCompte)->first();

        return [
            'compte' => $compte,
            'parent' => $compte->parent,
            'hierarchie' => $compte->hierarchie,
            'chemin' => $compte->chemin,
            'enfants' => $compte->enfants,
            'est_utilisable' => $compte->est_feuille
        ];
    }

    // Lister les comptes clients
    public function clients()
    {
        return CompteComptable::where('numero_compte', 'LIKE', '41%')
            ->utilisables()
            ->get();
    }

    // Lister les comptes de charges
    public function charges()
    {
        return CompteComptable::classe(6)
            ->niveau(2)
            ->get();
    }

    // Rechercher un compte
    public function rechercher($terme)
    {
        return CompteComptable::where('libelle', 'LIKE', "%{$terme}%")
            ->orWhere('numero_compte', 'LIKE', "%{$terme}%")
            ->get()
            ->map(function ($compte) {
                return [
                    'numero_compte' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'chemin' => $compte->chemin
                ];
            });
    }

    // Arbre comptable d'une classe
    public function arbre($classe)
    {
        $comptes = CompteComptable::classe($classe)
            ->orderBy('numero_compte')
            ->get();

        return $this->construireArbre($comptes);
    }

    private function construireArbre($comptes, $parent = null)
    {
        $arbre = [];

        foreach ($comptes as $compte) {
            if ($parent === null && $compte->niveau === 1) {
                $arbre[] = [
                    'compte' => $compte,
                    'enfants' => $this->construireArbre($comptes, $compte->numero_compte)
                ];
            } elseif ($parent !== null && strlen($compte->numero_compte) === strlen($parent) + 1
                && substr($compte->numero_compte, 0, -1) === $parent) {
                $arbre[] = [
                    'compte' => $compte,
                    'enfants' => $this->construireArbre($comptes, $compte->numero_compte)
                ];
            }
        }

        return $arbre;
    }
}
```

### Route API

```php
// routes/api.php
Route::prefix('comptable')->group(function () {
    Route::get('comptes/{numero}', [ComptableController::class, 'show']);
    Route::get('clients', [ComptableController::class, 'clients']);
    Route::get('charges', [ComptableController::class, 'charges']);
    Route::get('rechercher/{terme}', [ComptableController::class, 'rechercher']);
    Route::get('arbre/{classe}', [ComptableController::class, 'arbre']);
});
```

### Composant Vue.js pour afficher la hiérarchie

```vue
<template>
  <div class="compte-hierarchie">
    <nav aria-label="breadcrumb">
      <ol class="breadcrumb">
        <li
          v-for="(item, index) in hierarchie"
          :key="item.numero_compte"
          class="breadcrumb-item"
          :class="{ active: index === hierarchie.length - 1 }"
        >
          <a v-if="index < hierarchie.length - 1" href="#" @click.prevent="selectCompte(item)">
            {{ item.numero_compte }} - {{ item.libelle }}
          </a>
          <span v-else>{{ item.numero_compte }} - {{ item.libelle }}</span>
        </li>
      </ol>
    </nav>
  </div>
</template>

<script>
export default {
  props: {
    compte: {
      type: Object,
      required: true
    }
  },
  computed: {
    hierarchie() {
      return this.compte.hierarchie || [];
    }
  },
  methods: {
    selectCompte(compte) {
      this.$emit('compte-selected', compte);
    }
  }
};
</script>
```

## Résumé

Pour trouver le parent d'un compte :

1. **SQL simple** : `LEFT(numero_compte, LENGTH(numero_compte) - 1)`
2. **Fonction SQL** : `SELECT * FROM get_compte_parent('6011')`
3. **Laravel** : `$compte->parent`
4. **Vue complète** : Utiliser `v_comptes_avec_parents`

Le fichier `fonctions_plan_comptable.sql` contient 9 fonctions utiles pour gérer la hiérarchie des comptes !
