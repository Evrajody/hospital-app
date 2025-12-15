<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

/**
 * Modèle pour le Plan Comptable OHADA
 *
 * @property int $id
 * @property string $numero_compte
 * @property string $libelle
 * @property int $classe
 * @property int $niveau
 * @property string $type_compte
 * @property bool $utilisable
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 *
 * @property-read CompteComptable|null $parent
 * @property-read Collection $enfants
 * @property-read Collection $descendants
 * @property-read Collection $hierarchie
 * @property-read string $chemin
 * @property-read bool $est_feuille
 * @property-read bool $est_racine
 * @property-read string $type_compte_libelle
 * @property-read string $classe_libelle
 */
class CompteComptable extends Model
{
    /**
     * Nom de la table
     */
    protected $table = 'plan_comptable_ohada';

    /**
     * Les attributs mass assignable
     */
    protected $fillable = [
        'numero_compte',
        'libelle',
        'classe',
        'niveau',
        'type_compte',
        'utilisable',
    ];

    /**
     * Les attributs qui doivent être castés
     */
    protected $casts = [
        'classe' => 'integer',
        'niveau' => 'integer',
        'utilisable' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Les attributs accessibles (computed)
     */
    protected $appends = [
        'est_feuille',
        'est_racine',
        'type_compte_libelle',
        'classe_libelle',
    ];

    /**
     * Cache des relations pour optimisation
     */
    protected static $cacheHierarchie = [];

    /**
     * Constantes pour les types de comptes
     */
    const TYPE_ACTIF = 'ACTIF';
    const TYPE_PASSIF = 'PASSIF';
    const TYPE_CHARGE = 'CHARGE';
    const TYPE_PRODUIT = 'PRODUIT';
    const TYPE_SPECIAL = 'SPECIAL';

    /**
     * Constantes pour les classes
     */
    const CLASSE_CAPITAUX = 1;
    const CLASSE_IMMOBILISATIONS = 2;
    const CLASSE_STOCKS = 3;
    const CLASSE_TIERS = 4;
    const CLASSE_TRESORERIE = 5;
    const CLASSE_CHARGES = 6;
    const CLASSE_PRODUITS = 7;
    const CLASSE_HAO = 8;
    const CLASSE_ANALYTIQUE = 9;

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * Obtenir le compte parent direct
     */
    public function parent()
    {
        if (strlen($this->numero_compte) <= 1) {
            return null;
        }

        $numeroParent = substr($this->numero_compte, 0, -1);

        return $this->belongsTo(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', $numeroParent);
    }

    /**
     * Obtenir tous les enfants directs
     */
    public function enfants()
    {
        return $this->hasMany(self::class, 'numero_compte', 'numero_compte')
            ->whereRaw('numero_compte LIKE ? AND LENGTH(numero_compte) = ?', [
                $this->numero_compte . '%',
                strlen($this->numero_compte) + 1
            ])
            ->orderBy('numero_compte');
    }

    /**
     * Obtenir tous les descendants (enfants, petits-enfants, etc.)
     */
    public function descendants()
    {
        return $this->hasMany(self::class, 'numero_compte', 'numero_compte')
            ->where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->where('numero_compte', '!=', $this->numero_compte)
            ->orderBy('numero_compte');
    }

    /**
     * Obtenir les comptes de même niveau (siblings)
     */
    public function siblings()
    {
        $query = self::where('niveau', $this->niveau)
            ->where('classe', $this->classe)
            ->where('id', '!=', $this->id);

        // Si le compte a un parent, filtrer par le même parent
        if ($this->parent) {
            $parentNumero = substr($this->numero_compte, 0, -1);
            $query->whereRaw('LEFT(numero_compte, ?) = ?', [
                strlen($parentNumero),
                $parentNumero
            ]);
        }

        return $query->orderBy('numero_compte');
    }

    // ==========================================
    // ATTRIBUTS CALCULÉS (ACCESSORS)
    // ==========================================

    /**
     * Obtenir la hiérarchie complète (du compte jusqu'à la racine)
     */
    public function getHierarchieAttribute(): Collection
    {
        $cacheKey = "hierarchie_{$this->numero_compte}";

        return Cache::remember($cacheKey, 3600, function () {
            $hierarchie = collect([$this]);
            $compteCourant = $this;

            while ($parent = $compteCourant->parent) {
                $hierarchie->prepend($parent);
                $compteCourant = $parent;
            }

            return $hierarchie;
        });
    }

    /**
     * Obtenir le chemin complet (breadcrumb)
     */
    public function getCheminAttribute(): string
    {
        return $this->hierarchie
            ->map(fn($compte) => "{$compte->numero_compte} - {$compte->libelle}")
            ->implode(' > ');
    }

    /**
     * Vérifier si c'est un compte feuille (utilisable directement)
     */
    public function getEstFeuilleAttribute(): bool
    {
        return !self::where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->where('numero_compte', '!=', $this->numero_compte)
            ->exists();
    }

    /**
     * Vérifier si c'est un compte racine (niveau 1)
     */
    public function getEstRacineAttribute(): bool
    {
        return $this->niveau === 1;
    }

    /**
     * Obtenir le libellé du type de compte
     */
    public function getTypeCompteLibelleAttribute(): string
    {
        return match($this->type_compte) {
            self::TYPE_ACTIF => 'Actif',
            self::TYPE_PASSIF => 'Passif',
            self::TYPE_CHARGE => 'Charge',
            self::TYPE_PRODUIT => 'Produit',
            self::TYPE_SPECIAL => 'Spécial',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé de la classe
     */
    public function getClasseLibelleAttribute(): string
    {
        return match($this->classe) {
            self::CLASSE_CAPITAUX => 'Ressources durables',
            self::CLASSE_IMMOBILISATIONS => 'Actif immobilisé',
            self::CLASSE_STOCKS => 'Stocks',
            self::CLASSE_TIERS => 'Tiers',
            self::CLASSE_TRESORERIE => 'Trésorerie',
            self::CLASSE_CHARGES => 'Charges',
            self::CLASSE_PRODUITS => 'Produits',
            self::CLASSE_HAO => 'H.A.O.',
            self::CLASSE_ANALYTIQUE => 'Comptabilité analytique',
            default => 'Classe inconnue',
        };
    }

    /**
     * Obtenir le numéro du compte parent
     */
    public function getNumeroCompteParentAttribute(): ?string
    {
        if (strlen($this->numero_compte) <= 1) {
            return null;
        }

        return substr($this->numero_compte, 0, -1);
    }

    /**
     * Obtenir la profondeur dans l'arbre
     */
    public function getProfondeurAttribute(): int
    {
        return strlen($this->numero_compte);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Filtrer par classe
     */
    public function scopeClasse(Builder $query, int $classe): Builder
    {
        return $query->where('classe', $classe);
    }

    /**
     * Filtrer par niveau
     */
    public function scopeNiveau(Builder $query, int $niveau): Builder
    {
        return $query->where('niveau', $niveau);
    }

    /**
     * Filtrer par type de compte
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type_compte', $type);
    }

    /**
     * Obtenir uniquement les comptes utilisables (feuilles)
     */
    public function scopeUtilisables(Builder $query): Builder
    {
        return $query->whereRaw('NOT EXISTS (
            SELECT 1 FROM plan_comptable_ohada c2
            WHERE c2.numero_compte LIKE CONCAT(plan_comptable_ohada.numero_compte, \'%\')
            AND LENGTH(c2.numero_compte) > LENGTH(plan_comptable_ohada.numero_compte)
        )');
    }

    /**
     * Obtenir uniquement les comptes racines (niveau 1)
     */
    public function scopeRacines(Builder $query): Builder
    {
        return $query->where('niveau', 1);
    }

    /**
     * Filtrer par numéro de compte (pattern)
     */
    public function scopeCommencePar(Builder $query, string $pattern): Builder
    {
        return $query->where('numero_compte', 'LIKE', $pattern . '%');
    }

    /**
     * Rechercher dans le libellé
     */
    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('libelle', 'ILIKE', "%{$terme}%")
              ->orWhere('numero_compte', 'LIKE', "%{$terme}%");
        });
    }

    /**
     * Comptes d'actif (bilan)
     */
    public function scopeActif(Builder $query): Builder
    {
        return $query->whereIn('classe', [
            self::CLASSE_IMMOBILISATIONS,
            self::CLASSE_STOCKS,
            self::CLASSE_TRESORERIE,
        ])->orWhere(function ($q) {
            $q->where('classe', self::CLASSE_TIERS)
              ->where('type_compte', self::TYPE_ACTIF);
        });
    }

    /**
     * Comptes de passif (bilan)
     */
    public function scopePassif(Builder $query): Builder
    {
        return $query->where('classe', self::CLASSE_CAPITAUX)
            ->orWhere(function ($q) {
                $q->where('classe', self::CLASSE_TIERS)
                  ->where('type_compte', self::TYPE_PASSIF);
            });
    }

    /**
     * Comptes de charges (compte de résultat)
     */
    public function scopeCharges(Builder $query): Builder
    {
        return $query->where('classe', self::CLASSE_CHARGES);
    }

    /**
     * Comptes de produits (compte de résultat)
     */
    public function scopeProduits(Builder $query): Builder
    {
        return $query->where('classe', self::CLASSE_PRODUITS);
    }

    // ==========================================
    // MÉTHODES PUBLIQUES
    // ==========================================

    /**
     * Obtenir tous les ancêtres (parents récursifs)
     */
    public function getAncetres(): Collection
    {
        return $this->hierarchie->slice(0, -1);
    }

    /**
     * Vérifier si ce compte est un ancêtre d'un autre compte
     */
    public function estAncetre(self $compte): bool
    {
        return str_starts_with($compte->numero_compte, $this->numero_compte)
            && $compte->numero_compte !== $this->numero_compte;
    }

    /**
     * Vérifier si ce compte est un descendant d'un autre compte
     */
    public function estDescendant(self $compte): bool
    {
        return str_starts_with($this->numero_compte, $compte->numero_compte)
            && $this->numero_compte !== $compte->numero_compte;
    }

    /**
     * Obtenir le compte racine de la classe
     */
    public function getRacine(): ?self
    {
        return self::where('classe', $this->classe)
            ->where('niveau', 1)
            ->first();
    }

    /**
     * Construire l'arbre complet des descendants
     */
    public function getArbre(): array
    {
        return [
            'compte' => $this,
            'enfants' => $this->enfants->map(fn($enfant) => $enfant->getArbre())->toArray()
        ];
    }

    /**
     * Obtenir le niveau de profondeur maximum des descendants
     */
    public function getProfondeurMaxDescendants(): int
    {
        $maxNiveau = DB::table('plan_comptable_ohada')
            ->where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->max('niveau');

        return $maxNiveau ?? $this->niveau;
    }

    /**
     * Compter le nombre total de descendants
     */
    public function countDescendants(): int
    {
        return self::where('numero_compte', 'LIKE', $this->numero_compte . '%')
            ->where('numero_compte', '!=', $this->numero_compte)
            ->count();
    }

    /**
     * Vérifier si le compte peut être utilisé dans une écriture
     */
    public function peutEtreUtilise(): bool
    {
        return $this->utilisable && $this->est_feuille;
    }

    /**
     * Obtenir le format court du compte (numéro uniquement)
     */
    public function getFormatCourt(): string
    {
        return $this->numero_compte;
    }

    /**
     * Obtenir le format long du compte (numéro + libellé)
     */
    public function getFormatLong(): string
    {
        return "{$this->numero_compte} - {$this->libelle}";
    }

    /**
     * Obtenir le format complet avec hiérarchie
     */
    public function getFormatComplet(): string
    {
        return $this->chemin;
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Rechercher des comptes par terme
     */
    public static function rechercherComptes(string $terme): Collection
    {
        return self::recherche($terme)
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Obtenir tous les comptes d'une classe
     */
    public static function parClasse(int $classe): Collection
    {
        return self::classe($classe)
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Obtenir l'arbre complet d'une classe
     */
    public static function arbreClasse(int $classe): Collection
    {
        return self::classe($classe)
            ->racines()
            ->get()
            ->map(fn($racine) => $racine->getArbre());
    }

    /**
     * Valider un numéro de compte
     */
    public static function validerNumeroCompte(string $numeroCompte): array
    {
        $compte = self::where('numero_compte', $numeroCompte)->first();

        if ($compte) {
            return [
                'valide' => true,
                'message' => 'Compte valide',
                'compte' => $compte,
                'utilisable' => $compte->peutEtreUtilise(),
            ];
        }

        // Chercher le parent
        if (strlen($numeroCompte) > 1) {
            $numeroParent = substr($numeroCompte, 0, -1);
            $parent = self::where('numero_compte', $numeroParent)->first();

            if ($parent) {
                return [
                    'valide' => false,
                    'message' => 'Compte inexistant, mais le parent existe',
                    'compte' => null,
                    'parent' => $parent,
                ];
            }
        }

        return [
            'valide' => false,
            'message' => 'Compte inexistant',
            'compte' => null,
        ];
    }

    /**
     * Obtenir les comptes clients
     */
    public static function clients(): Collection
    {
        return self::commencePar('41')
            ->utilisables()
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Obtenir les comptes fournisseurs
     */
    public static function fournisseurs(): Collection
    {
        return self::commencePar('40')
            ->utilisables()
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Obtenir les comptes de banque
     */
    public static function banques(): Collection
    {
        return self::commencePar('52')
            ->utilisables()
            ->orderBy('numero_compte')
            ->get();
    }

    /**
     * Obtenir les comptes de caisse
     */
    public static function caisses(): Collection
    {
        return self::commencePar('57')
            ->utilisables()
            ->orderBy('numero_compte')
            ->get();
    }

    // ==========================================
    // MÉTHODES DE FORMATAGE
    // ==========================================

    /**
     * Convertir en tableau pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'numero_compte' => $this->numero_compte,
            'libelle' => $this->libelle,
            'classe' => $this->classe,
            'classe_libelle' => $this->classe_libelle,
            'niveau' => $this->niveau,
            'type_compte' => $this->type_compte,
            'type_compte_libelle' => $this->type_compte_libelle,
            'utilisable' => $this->utilisable,
            'est_feuille' => $this->est_feuille,
            'est_racine' => $this->est_racine,
            'numero_compte_parent' => $this->numero_compte_parent,
            'chemin' => $this->chemin,
        ];
    }

    /**
     * Convertir en option pour un select
     */
    public function toSelectOption(): array
    {
        return [
            'value' => $this->numero_compte,
            'label' => $this->getFormatLong(),
            'disabled' => !$this->peutEtreUtilise(),
        ];
    }

    // ==========================================
    // EVENTS
    // ==========================================

    /**
     * Boot du modèle
     */
    protected static function boot()
    {
        parent::boot();

        // Nettoyer le cache lors des modifications
        static::saved(function ($compte) {
            Cache::forget("hierarchie_{$compte->numero_compte}");
        });

        static::deleted(function ($compte) {
            Cache::forget("hierarchie_{$compte->numero_compte}");
        });
    }
}
