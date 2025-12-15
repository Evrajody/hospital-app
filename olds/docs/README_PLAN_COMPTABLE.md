# Plan Comptable OHADA - Documentation Complète

## Vue d'ensemble

Système complet de gestion du Plan Comptable OHADA pour Laravel avec :
- ✅ **800+ comptes** comptables (9 classes)
- ✅ **Modèle Eloquent** avec relations hiérarchiques
- ✅ **Service dédié** avec fonctionnalités avancées
- ✅ **20+ helpers** pour faciliter l'utilisation
- ✅ **Migration et Seeder** prêts à l'emploi
- ✅ **Documentation complète** et exemples

## Fichiers créés

### Fichiers SQL
- `plan_comptable_ohada.sql` (63 KB) - Plan comptable complet
- `fonctions_plan_comptable.sql` (10 KB) - 9 fonctions PostgreSQL

### Fichiers Laravel
- `app/Models/CompteComptable.php` (18 KB) - Modèle Eloquent complet
- `app/Services/PlanComptableService.php` (11 KB) - Service métier
- `app/Helpers/comptable_helpers.php` (7 KB) - 20+ fonctions helper
- `database/migrations/2025_01_01_000001_create_plan_comptable_ohada_table.php` (3 KB)
- `database/seeders/PlanComptableOhadaSeeder.php` (4 KB)

### Documentation
- `PLAN_COMPTABLE_OHADA_GUIDE.md` (9 KB) - Guide général
- `EXEMPLES_PLAN_COMPTABLE.md` (12 KB) - Exemples SQL
- `GUIDE_UTILISATION_LARAVEL.md` (15 KB) - Guide Laravel complet
- `README_PLAN_COMPTABLE.md` (ce fichier)

## Installation rapide

### 1. Copier les fichiers Laravel

```bash
# Les fichiers sont déjà créés dans votre projet :
# - app/Models/CompteComptable.php
# - app/Services/PlanComptableService.php
# - app/Helpers/comptable_helpers.php
# - database/migrations/...
# - database/seeders/...
```

### 2. Configurer l'autoload

Dans `composer.json` :

```json
{
    "autoload": {
        "psr-4": {
            "App\\": "app/",
            "Database\\Factories\\": "database/factories/",
            "Database\\Seeders\\": "database/seeders/"
        },
        "files": [
            "app/Helpers/comptable_helpers.php"
        ]
    }
}
```

Puis :
```bash
make shell
composer dump-autoload
```

### 3. Exécuter les migrations

```bash
make shell

# Exécuter la migration
php artisan migrate

# Importer les données
php artisan db:seed --class=PlanComptableOhadaSeeder
```

## Utilisation rapide

### Modèle Eloquent

```php
use App\Models\CompteComptable;

// Récupérer un compte
$compte = CompteComptable::where('numero_compte', '6011')->first();

// Obtenir le parent
$parent = $compte->parent;

// Obtenir la hiérarchie complète
$hierarchie = $compte->hierarchie;

// Obtenir les enfants
$enfants = $compte->enfants;

// Attributs calculés
echo $compte->chemin;          // Breadcrumb complet
echo $compte->est_feuille;     // true/false
echo $compte->classe_libelle;  // "Charges"

// Scopes
$charges = CompteComptable::classe(6)->utilisables()->get();
$clients = CompteComptable::clients();
$banques = CompteComptable::banques();
```

### Helpers

```php
// Récupérer un compte
$compte = compte('6011');

// Lister par classe
$charges = comptes_classe(6);

// Comptes utilisables
$utilisables = comptes_utilisables(classe: 4);

// Comptes spécifiques
$clients = comptes_clients();
$fournisseurs = comptes_fournisseurs();

// Validation
$validation = valider_compte('6011');

// Recherche
$resultats = rechercher_comptes('client');

// Formatage
echo formater_compte('6011', 'long');
// "6011 - Achats de marchandises groupe A"
```

### Service

```php
use App\Services\PlanComptableService;

$service = app(PlanComptableService::class);
// Ou
$service = plan_comptable();

// Arbre d'une classe
$arbre = $service->getArbreClasse(6);

// Recherche avancée
$resultats = $service->rechercher('client', classe: 4);

// Comptes pour le bilan
$bilan = $service->getComptesBilan();

// Statistiques
$stats = $service->getStatistiques();

// Export
$json = $service->exporter('json');
$csv = $service->exporter('csv', classe: 6);
```

## Fonctionnalités principales

### Modèle CompteComptable

#### Relations
- `parent()` - Compte parent direct
- `enfants()` - Enfants directs
- `descendants()` - Tous les descendants
- `siblings()` - Comptes de même niveau

#### Attributs calculés
- `hierarchie` - Collection de tous les parents
- `chemin` - Breadcrumb complet
- `est_feuille` - Compte utilisable ?
- `est_racine` - Compte de niveau 1 ?
- `classe_libelle` - Nom de la classe
- `type_compte_libelle` - Type formaté

#### Scopes (30+)
- `classe($classe)` - Filtrer par classe
- `niveau($niveau)` - Filtrer par niveau
- `type($type)` - Filtrer par type
- `utilisables()` - Comptes feuilles uniquement
- `racines()` - Comptes de niveau 1
- `commencePar($pattern)` - Pattern sur numéro
- `recherche($terme)` - Recherche textuelle
- `actif()` - Comptes d'actif
- `passif()` - Comptes de passif
- `charges()` - Comptes de charges
- `produits()` - Comptes de produits

#### Méthodes statiques
- `clients()` - Tous les comptes clients
- `fournisseurs()` - Tous les comptes fournisseurs
- `banques()` - Tous les comptes banques
- `caisses()` - Tous les comptes caisses
- `rechercherComptes($terme)` - Recherche
- `validerNumeroCompte($numero)` - Validation
- `parClasse($classe)` - Par classe
- `arbreClasse($classe)` - Arbre complet

#### Méthodes d'instance
- `getAncetres()` - Tous les parents
- `estAncetre($compte)` - Vérification
- `estDescendant($compte)` - Vérification
- `getRacine()` - Racine de la classe
- `getArbre()` - Arbre récursif
- `peutEtreUtilise()` - Validation usage
- `toApiArray()` - Format API
- `toSelectOption()` - Format select HTML

### Service PlanComptableService

- `getArbreClasse($classe)` - Arbre complet avec cache
- `rechercher($terme, $classe, $utilisablesUniquement)` - Recherche avancée
- `getComptesBilan()` - Comptes pour bilan
- `getComptesResultat()` - Comptes pour résultat
- `suggererComptes($pattern, $limit)` - Autocomplétion
- `validerComptes($array)` - Validation en masse
- `getComptesFrequents($limit)` - Comptes courants
- `getStatistiques()` - Stats complètes
- `getCompteAvecHierarchie($numero)` - Détail complet
- `exporter($format, $classe)` - Export JSON/CSV/Excel
- `importerComptesPersonnalises($array)` - Import custom

### Helpers (20+ fonctions)

- `compte($numero)` - Récupérer un compte
- `comptes_classe($classe)` - Par classe
- `comptes_utilisables($classe)` - Utilisables
- `comptes_clients()` - Clients
- `comptes_fournisseurs()` - Fournisseurs
- `comptes_banques()` - Banques
- `comptes_caisses()` - Caisses
- `valider_compte($numero)` - Validation
- `rechercher_comptes($terme)` - Recherche
- `plan_comptable()` - Instance du service
- `arbre_comptable($classe)` - Arbre
- `formater_compte($compte, $format)` - Formatage
- `est_compte_debit($compte)` - Vérification
- `est_compte_credit($compte)` - Vérification
- `get_classe_libelle($classe)` - Libellé
- `comptes_select_options($classe)` - Options select
- `stats_plan_comptable()` - Statistiques

## Structure du plan comptable

### Classe 1 : Ressources durables (Capitaux)
Capital, Réserves, Résultats, Emprunts, Provisions

### Classe 2 : Actif immobilisé
Immobilisations incorporelles, corporelles, financières

### Classe 3 : Stocks
Marchandises, Matières, Produits, Dépréciation

### Classe 4 : Tiers
Fournisseurs, Clients, Personnel, État, Organismes

### Classe 5 : Trésorerie
Banques, Caisses, Titres de placement

### Classe 6 : Charges
Achats, Services, Personnel, Dotations

### Classe 7 : Produits
Ventes, Subventions, Production, Revenus

### Classe 8 : H.A.O.
Hors Activités Ordinaires, Cessions, Impôts

### Classe 9 : Comptabilité analytique
Sections, Coûts, Résultats analytiques

## Exemples d'utilisation

### Dans un contrôleur

```php
public function index()
{
    // Récupérer les comptes clients
    $clients = comptes_clients();

    // Recherche
    $resultats = rechercher_comptes(request('q'));

    // Avec le service
    $arbre = plan_comptable()->getArbreClasse(6);

    return view('comptable.index', compact('clients', 'resultats', 'arbre'));
}
```

### Dans une vue Blade

```blade
<select name="compte" class="form-select">
    @foreach(comptes_utilisables(classe: 4) as $compte)
        <option value="{{ $compte->numero_compte }}">
            {{ $compte->getFormatLong() }}
        </option>
    @endforeach
</select>

<!-- Afficher la hiérarchie -->
<nav>
    <ol class="breadcrumb">
        @foreach($compte->hierarchie as $ancetre)
            <li class="breadcrumb-item">
                {{ $ancetre->numero_compte }} - {{ $ancetre->libelle }}
            </li>
        @endforeach
    </ol>
</nav>
```

### API REST

```php
Route::get('/api/comptes/search', function (Request $request) {
    return plan_comptable()->rechercher($request->q);
});

Route::get('/api/comptes/{numero}', function ($numero) {
    return plan_comptable()->getCompteAvecHierarchie($numero);
});
```

## Tests

```bash
# Créer un test
php artisan make:test CompteComptableTest --unit

# Exécuter les tests
php artisan test
```

## Commandes Make disponibles

```bash
# Import initial
make start
make migrate
php artisan db:seed --class=PlanComptableOhadaSeeder

# Voir les logs
make logs-app

# Shell
make shell
```

## Performance

- **Cache activé** sur les hiérarchies (1h TTL)
- **Index optimisés** sur numero_compte, classe, niveau
- **Index full-text** pour recherche rapide
- **Eager loading** disponible pour relations
- **Query optimization** avec scopes

## Support et documentation

- **Guide général** : `PLAN_COMPTABLE_OHADA_GUIDE.md`
- **Exemples SQL** : `EXEMPLES_PLAN_COMPTABLE.md`
- **Guide Laravel** : `GUIDE_UTILISATION_LARAVEL.md`
- **Ce fichier** : `README_PLAN_COMPTABLE.md`

## Ressources externes

- [Site OHADA](http://www.ohada.org)
- [Acte uniforme relatif au droit comptable](http://www.ohada.org/index.php/fr/actes-uniformes)

## Licence

Plan Comptable OHADA - Organisation pour l'Harmonisation en Afrique du Droit des Affaires

---

**Développé pour Hospital App** - Laravel 11 - PostgreSQL 16
