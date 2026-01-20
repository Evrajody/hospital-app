# Guide d'utilisation - Plan Comptable OHADA avec Laravel

## Installation et Configuration

### 1. Charger les helpers

Ajoutez dans `composer.json` :

```json
{
    "autoload": {
        "files": [
            "app/Helpers/comptable_helpers.php"
        ]
    }
}
```

Puis exécutez :
```bash
composer dump-autoload
```

### 2. Enregistrer le Service Provider (optionnel)

Créez `app/Providers/ComptableServiceProvider.php` :

```php
<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\PlanComptableService;

class ComptableServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->app->singleton(PlanComptableService::class, function ($app) {
            return new PlanComptableService();
        });
    }
}
```

Ajoutez dans `config/app.php` :

```php
'providers' => [
    // ...
    App\Providers\ComptableServiceProvider::class,
],
```

### 3. Exécuter les migrations

```bash
# Créer la table
php artisan migrate

# Importer les données du plan comptable
php artisan db:seed --class=PlanComptableOhadaSeeder
```

## Utilisation du Modèle

### Récupérer un compte

```php
use App\Models\CompteComptable;

// Par numéro de compte
$compte = CompteComptable::where('numero_compte', '6011')->first();

// Avec le helper
$compte = compte('6011');

// Avec validation
$validation = CompteComptable::validerNumeroCompte('6011');
if ($validation['valide']) {
    $compte = $validation['compte'];
}
```

### Relations hiérarchiques

```php
$compte = compte('6011');

// Parent direct
$parent = $compte->parent;
// Retourne: 601 - Achats de marchandises

// Tous les ancêtres (hiérarchie complète)
$hierarchie = $compte->hierarchie;
// Collection: [6, 60, 601, 6011]

// Enfants directs
$enfants = $compte->enfants;

// Tous les descendants
$descendants = $compte->descendants;

// Comptes de même niveau (siblings)
$siblings = $compte->siblings()->get();
```

### Attributs calculés

```php
$compte = compte('6011');

// Chemin complet (breadcrumb)
echo $compte->chemin;
// "6 - ACHATS > 60 - ACHATS > 601 - Achats de marchandises > 6011 - Achats groupe A"

// Vérifications
$compte->est_feuille;      // true/false
$compte->est_racine;       // true/false
$compte->est_utilisable;   // true/false

// Libellés
$compte->classe_libelle;         // "Charges"
$compte->type_compte_libelle;    // "Charge"

// Formatage
$compte->getFormatCourt();    // "6011"
$compte->getFormatLong();     // "6011 - Achats de marchandises groupe A"
$compte->getFormatComplet();  // Chemin complet
```

### Scopes (filtres)

```php
// Par classe
$comptesCharges = CompteComptable::classe(6)->get();
$comptesProduits = CompteComptable::classe(7)->get();

// Par niveau
$comptesNiveau2 = CompteComptable::niveau(2)->get();

// Par type
$comptesActif = CompteComptable::type('ACTIF')->get();

// Comptes utilisables (feuilles uniquement)
$comptesUtilisables = CompteComptable::utilisables()->get();

// Comptes racines
$racines = CompteComptable::racines()->get();

// Pattern de recherche
$comptesAchats = CompteComptable::commencePar('60')->get();

// Recherche textuelle
$resultats = CompteComptable::recherche('client')->get();

// Combinaisons
$clientsUtilisables = CompteComptable::commencePar('41')
    ->utilisables()
    ->get();
```

### Méthodes statiques

```php
// Comptes spécifiques
$clients = CompteComptable::clients();
$fournisseurs = CompteComptable::fournisseurs();
$banques = CompteComptable::banques();
$caisses = CompteComptable::caisses();

// Recherche
$resultats = CompteComptable::rechercherComptes('client');

// Par classe
$comptesClasse4 = CompteComptable::parClasse(4);

// Arbre d'une classe
$arbreCharges = CompteComptable::arbreClasse(6);
```

## Utilisation du Service

```php
use App\Services\PlanComptableService;

$service = app(PlanComptableService::class);
// Ou avec le helper
$service = plan_comptable();

// Arbre d'une classe
$arbre = $service->getArbreClasse(6);

// Recherche
$resultats = $service->rechercher('client', classe: 4, utilisablesUniquement: true);

// Comptes pour le bilan
$bilan = $service->getComptesBilan();
// [
//     'actif' => ['immobilisations' => [...], 'stocks' => [...], ...],
//     'passif' => ['capitaux' => [...], 'dettes' => [...]]
// ]

// Comptes pour le compte de résultat
$resultat = $service->getComptesResultat();
// ['charges' => [...], 'produits' => [...]]

// Suggestions
$suggestions = $service->suggererComptes('60', limit: 10);

// Statistiques
$stats = $service->getStatistiques();

// Compte avec hiérarchie complète
$detail = $service->getCompteAvecHierarchie('6011');

// Export
$json = $service->exporter('json', classe: 6);
$csv = $service->exporter('csv');
```

## Utilisation des Helpers

```php
// Récupérer un compte
$compte = compte('6011');

// Par classe
$charges = comptes_classe(6);

// Comptes utilisables
$utilisables = comptes_utilisables(classe: 4);

// Comptes spécifiques
$clients = comptes_clients();
$fournisseurs = comptes_fournisseurs();
$banques = comptes_banques();
$caisses = comptes_caisses();

// Validation
$validation = valider_compte('6011');

// Recherche
$resultats = rechercher_comptes('client');

// Arbre comptable
$arbre = arbre_comptable(6);

// Formatage
echo formater_compte('6011', 'long');
echo formater_compte($compte, 'complet');

// Vérifications
$estDebit = est_compte_debit('6011');    // true (charge)
$estCredit = est_compte_credit('7011');  // true (produit)

// Libellé de classe
echo get_classe_libelle(6);  // "Charges"

// Options pour select
$options = comptes_select_options(classe: 4, utilisablesUniquement: true);

// Statistiques
$stats = stats_plan_comptable();
```

## Exemples d'utilisation dans un contrôleur

### Contrôleur de base

```php
<?php

namespace App\Http\Controllers;

use App\Models\CompteComptable;
use App\Services\PlanComptableService;
use Illuminate\Http\Request;

class ComptableController extends Controller
{
    public function __construct(
        private PlanComptableService $planComptable
    ) {}

    /**
     * Lister les comptes
     */
    public function index(Request $request)
    {
        $query = CompteComptable::query();

        // Filtres
        if ($request->has('classe')) {
            $query->classe($request->classe);
        }

        if ($request->has('utilisables')) {
            $query->utilisables();
        }

        if ($request->has('recherche')) {
            $query->recherche($request->recherche);
        }

        $comptes = $query->orderBy('numero_compte')->paginate(50);

        return view('comptable.index', compact('comptes'));
    }

    /**
     * Afficher un compte
     */
    public function show(string $numeroCompte)
    {
        $detail = $this->planComptable->getCompteAvecHierarchie($numeroCompte);

        if (!$detail) {
            abort(404, 'Compte introuvable');
        }

        return view('comptable.show', $detail);
    }

    /**
     * Obtenir l'arbre d'une classe
     */
    public function arbre(int $classe)
    {
        $arbre = $this->planComptable->getArbreClasse($classe);

        return response()->json($arbre);
    }

    /**
     * Recherche (API)
     */
    public function search(Request $request)
    {
        $resultats = $this->planComptable->rechercher(
            $request->q,
            classe: $request->classe,
            utilisablesUniquement: $request->boolean('utilisables', false)
        );

        return response()->json([
            'data' => $resultats->map->toApiArray(),
        ]);
    }

    /**
     * Suggérer des comptes (autocomplete)
     */
    public function suggest(Request $request)
    {
        $suggestions = $this->planComptable->suggererComptes(
            $request->q,
            limit: $request->input('limit', 10)
        );

        return response()->json([
            'suggestions' => $suggestions->map(fn($c) => [
                'value' => $c->numero_compte,
                'label' => $c->getFormatLong(),
            ]),
        ]);
    }

    /**
     * Exporter
     */
    public function export(Request $request)
    {
        $format = $request->input('format', 'json');
        $classe = $request->input('classe');

        $export = $this->planComptable->exporter($format, $classe);

        return response($export)
            ->header('Content-Type', match($format) {
                'json' => 'application/json',
                'csv' => 'text/csv',
                default => 'application/octet-stream',
            })
            ->header('Content-Disposition', 'attachment; filename="plan_comptable.' . $format . '"');
    }
}
```

### Routes API

```php
// routes/api.php

use App\Http\Controllers\ComptableController;

Route::prefix('comptable')->group(function () {
    Route::get('/', [ComptableController::class, 'index']);
    Route::get('/search', [ComptableController::class, 'search']);
    Route::get('/suggest', [ComptableController::class, 'suggest']);
    Route::get('/arbre/{classe}', [ComptableController::class, 'arbre']);
    Route::get('/export', [ComptableController::class, 'export']);
    Route::get('/{numeroCompte}', [ComptableController::class, 'show']);
});
```

## Exemples dans les vues Blade

### Afficher un compte

```blade
@php
    $compte = compte('6011');
@endphp

<div class="compte">
    <h2>{{ $compte->getFormatLong() }}</h2>

    <!-- Hiérarchie (breadcrumb) -->
    <nav aria-label="breadcrumb">
        <ol class="breadcrumb">
            @foreach($compte->hierarchie as $ancetre)
                <li class="breadcrumb-item">
                    <a href="{{ route('comptable.show', $ancetre->numero_compte) }}">
                        {{ $ancetre->numero_compte }} - {{ $ancetre->libelle }}
                    </a>
                </li>
            @endforeach
        </ol>
    </nav>

    <!-- Informations -->
    <dl>
        <dt>Classe</dt>
        <dd>{{ $compte->classe_libelle }}</dd>

        <dt>Type</dt>
        <dd>{{ $compte->type_compte_libelle }}</dd>

        <dt>Niveau</dt>
        <dd>{{ $compte->niveau }}</dd>

        <dt>Utilisable</dt>
        <dd>
            @if($compte->peutEtreUtilise())
                <span class="badge bg-success">Oui</span>
            @else
                <span class="badge bg-secondary">Non (compte de regroupement)</span>
            @endif
        </dd>
    </dl>

    <!-- Enfants -->
    @if($compte->enfants->isNotEmpty())
        <h3>Sous-comptes</h3>
        <ul>
            @foreach($compte->enfants as $enfant)
                <li>
                    <a href="{{ route('comptable.show', $enfant->numero_compte) }}">
                        {{ $enfant->getFormatLong() }}
                    </a>
                </li>
            @endforeach
        </ul>
    @endif
</div>
```

### Select de comptes

```blade
<select name="compte_id" id="compte_id" class="form-select">
    <option value="">Sélectionnez un compte</option>
    @foreach(comptes_utilisables(classe: 4) as $compte)
        <option value="{{ $compte->numero_compte }}"
                {{ old('compte_id') == $compte->numero_compte ? 'selected' : '' }}>
            {{ $compte->getFormatLong() }}
        </option>
    @endforeach
</select>
```

### Autocomplete avec Alpine.js

```blade
<div x-data="compteAutocomplete()">
    <input
        type="text"
        x-model="search"
        @input.debounce.300ms="searchComptes"
        placeholder="Rechercher un compte..."
        class="form-control"
    >

    <div x-show="suggestions.length > 0" class="dropdown-menu show">
        <template x-for="suggestion in suggestions" :key="suggestion.value">
            <a
                href="#"
                class="dropdown-item"
                @click.prevent="selectCompte(suggestion)"
                x-text="suggestion.label"
            ></a>
        </template>
    </div>
</div>

<script>
function compteAutocomplete() {
    return {
        search: '',
        suggestions: [],

        async searchComptes() {
            if (this.search.length < 2) {
                this.suggestions = [];
                return;
            }

            const response = await fetch(`/api/comptable/suggest?q=${this.search}`);
            const data = await response.json();
            this.suggestions = data.suggestions;
        },

        selectCompte(suggestion) {
            this.search = suggestion.label;
            this.suggestions = [];
            // Émettre l'événement ou mettre à jour un champ caché
            this.$dispatch('compte-selected', suggestion);
        }
    }
}
</script>
```

## Tests unitaires

```php
<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Models\CompteComptable;
use Illuminate\Foundation\Testing\RefreshDatabase;

class CompteComptableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Charger le plan comptable
        $this->seed(\Database\Seeders\PlanComptableOhadaSeeder::class);
    }

    /** @test */
    public function un_compte_peut_avoir_un_parent()
    {
        $compte = CompteComptable::where('numero_compte', '6011')->first();
        $parent = $compte->parent;

        $this->assertNotNull($parent);
        $this->assertEquals('601', $parent->numero_compte);
    }

    /** @test */
    public function la_hierarchie_complete_est_recuperee()
    {
        $compte = CompteComptable::where('numero_compte', '6011')->first();
        $hierarchie = $compte->hierarchie;

        $this->assertCount(4, $hierarchie); // 6, 60, 601, 6011
        $this->assertEquals('6', $hierarchie->first()->numero_compte);
        $this->assertEquals('6011', $hierarchie->last()->numero_compte);
    }

    /** @test */
    public function les_comptes_utilisables_sont_filtres()
    {
        $utilisables = CompteComptable::utilisables()->count();
        $total = CompteComptable::count();

        $this->assertGreaterThan(0, $utilisables);
        $this->assertLessThan($total, $utilisables);
    }

    /** @test */
    public function la_validation_de_compte_fonctionne()
    {
        $validation = CompteComptable::validerNumeroCompte('6011');
        $this->assertTrue($validation['valide']);

        $validation = CompteComptable::validerNumeroCompte('9999');
        $this->assertFalse($validation['valide']);
    }
}
```

## Bonnes pratiques

1. **Toujours utiliser les comptes feuilles** pour les écritures comptables
2. **Mettre en cache** les arborescences fréquemment utilisées
3. **Valider les comptes** avant de les utiliser dans les écritures
4. **Utiliser les scopes** pour filtrer efficacement
5. **Exploiter les helpers** pour simplifier le code

## Commandes Artisan personnalisées (bonus)

Créez `app/Console/Commands/ComptableStatsCommand.php` :

```php
<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\PlanComptableService;

class ComptableStatsCommand extends Command
{
    protected $signature = 'comptable:stats';
    protected $description = 'Afficher les statistiques du plan comptable';

    public function handle(PlanComptableService $service)
    {
        $stats = $service->getStatistiques();

        $this->info('=== Statistiques du Plan Comptable OHADA ===');
        $this->newLine();

        $this->table(
            ['Classe', 'Libellé', 'Nombre de comptes'],
            collect($stats['par_classe'])->map(function ($count, $classe) {
                return [$classe, get_classe_libelle($classe), $count];
            })->toArray()
        );

        $this->info("Total: {$stats['total']} comptes");
        $this->info("Utilisables: {$stats['utilisables']} comptes");
    }
}
```

Utilisation :
```bash
php artisan comptable:stats
```
