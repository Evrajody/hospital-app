<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modèle Fournisseur
 *
 * @property int $id
 * @property string $nom
 * @property string|null $type_fournisseur
 * @property string|null $contact
 * @property string|null $fonction_contact
 * @property string|null $telephone
 * @property string|null $telephone_secondaire
 * @property string|null $email
 * @property string|null $site_web
 * @property string|null $adresse
 * @property string|null $ville
 * @property string $pays
 * @property int|null $compte_comptable_id
 * @property string|null $ifu
 * @property string|null $rccm
 * @property string|null $observations
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 * @property \Carbon\Carbon|null $deleted_at
 *
 * @property-read CompteComptable|null $compteComptable
 */
class Fournisseur extends Model
{
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'fournisseurs';

    /**
     * Les attributs mass assignable
     */
    protected $fillable = [
        'nom',
        'type_fournisseur',
        'contact',
        'fonction_contact',
        'telephone',
        'telephone_secondaire',
        'email',
        'site_web',
        'adresse',
        'ville',
        'pays',
        'compte_comptable_id',
        'ifu',
        'rccm',
        'observations',
    ];

    /**
     * Les attributs castés
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Constantes pour les types de fournisseurs
     */
    const TYPE_MEDICAMENTS = 'medicaments';
    const TYPE_EQUIPEMENTS = 'equipements';
    const TYPE_CONSOMMABLES = 'consommables';
    const TYPE_SERVICES = 'services';
    const TYPE_MAINTENANCE = 'maintenance';
    const TYPE_AUTRES = 'autres';

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * Relation avec le compte comptable principal (legacy).
     */
    public function compteComptable(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_comptable_id');
    }

    /**
     * Tous les comptes comptables rattachés (plusieurs possibles).
     */
    public function comptes()
    {
        return $this->belongsToMany(
            CompteComptable::class,
            'fournisseur_comptes',
            'fournisseur_id',
            'compte_comptable_id'
        )->withPivot('principal')->withTimestamps();
    }

    /**
     * Relation avec les factures fournisseurs
     */
    public function factures(): HasMany
    {
        return $this->hasMany(FactureFournisseur::class);
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Filtrer par type de fournisseur
     */
    public function scopeType(Builder $query, string $type): Builder
    {
        return $query->where('type_fournisseur', $type);
    }

    /**
     * Recherche globale
     */
    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('nom', 'ILIKE', "%{$terme}%")
              ->orWhere('contact', 'ILIKE', "%{$terme}%")
              ->orWhere('email', 'ILIKE', "%{$terme}%")
              ->orWhere('telephone', 'LIKE', "%{$terme}%")
              ->orWhere('ifu', 'LIKE', "%{$terme}%");
        });
    }

    /**
     * Avec compte comptable
     */
    public function scopeAvecCompte(Builder $query): Builder
    {
        return $query->whereNotNull('compte_comptable_id');
    }

    /**
     * Sans compte comptable
     */
    public function scopeSansCompte(Builder $query): Builder
    {
        return $query->whereNull('compte_comptable_id');
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Obtenir le libellé du type de fournisseur
     */
    public function getTypeFournisseurLibelleAttribute(): string
    {
        return match($this->type_fournisseur) {
            self::TYPE_MEDICAMENTS => 'Médicaments & Produits pharmaceutiques',
            self::TYPE_EQUIPEMENTS => 'Équipements médicaux',
            self::TYPE_CONSOMMABLES => 'Consommables & Fournitures',
            self::TYPE_SERVICES => 'Services (Eau, Électricité, etc.)',
            self::TYPE_MAINTENANCE => 'Maintenance & Réparations',
            self::TYPE_AUTRES => 'Autres',
            default => 'Non défini',
        };
    }

    /**
     * Obtenir le nom du pays
     */
    public function getPaysNomAttribute(): string
    {
        $pays = [
            'BJ' => 'Bénin',
            'TG' => 'Togo',
            'NE' => 'Niger',
            'BF' => 'Burkina Faso',
            'CI' => 'Côte d\'Ivoire',
            'GH' => 'Ghana',
            'NG' => 'Nigeria',
            'SN' => 'Sénégal',
            'FR' => 'France',
        ];

        return $pays[$this->pays] ?? $this->pays;
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Obtenir les statistiques globales des fournisseurs
     */
    public static function getStatistiques(): array
    {
        $total = self::count();

        // Statistiques par type
        $parType = self::select('type_fournisseur', DB::raw('COUNT(*) as count'))
            ->groupBy('type_fournisseur')
            ->pluck('count', 'type_fournisseur')
            ->toArray();

        // TODO: Calculer les dettes totales quand les factures seront implémentées
        $dettesTotal = 0;
        $facturesEnCours = 0;

        return [
            'total' => $total,
            'par_type' => $parType,
            'factures_en_cours' => $facturesEnCours,
            'dettes_total' => $dettesTotal,
        ];
    }

    /**
     * Obtenir les types de fournisseurs avec leurs libellés
     */
    public static function getTypesFournisseurs(): array
    {
        return [
            ['value' => self::TYPE_MEDICAMENTS, 'label' => 'Médicaments & Produits pharmaceutiques'],
            ['value' => self::TYPE_EQUIPEMENTS, 'label' => 'Équipements médicaux'],
            ['value' => self::TYPE_CONSOMMABLES, 'label' => 'Consommables & Fournitures'],
            ['value' => self::TYPE_SERVICES, 'label' => 'Services (Eau, Électricité, etc.)'],
            ['value' => self::TYPE_MAINTENANCE, 'label' => 'Maintenance & Réparations'],
            ['value' => self::TYPE_AUTRES, 'label' => 'Autres'],
        ];
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    /**
     * Vérifier si le fournisseur a un compte comptable
     */
    public function aCompteComptable(): bool
    {
        return $this->compte_comptable_id !== null;
    }

    /**
     * Obtenir le numéro de compte comptable
     */
    public function getNumeroCompte(): ?string
    {
        return $this->compteComptable?->numero_compte;
    }

    /**
     * Convertir en tableau pour l'API
     */
    /**
     * IDs des comptes supplémentaires (hors principal).
     */
    public function getComptesSupplementairesIdsAttribute(): array
    {
        return $this->comptes()
            ->where('fournisseur_comptes.principal', false)
            ->pluck('plan_comptable_ohada.id')
            ->toArray();
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type_fournisseur' => $this->type_fournisseur,
            'type_fournisseur_libelle' => $this->type_fournisseur_libelle,
            'contact' => $this->contact,
            'fonction_contact' => $this->fonction_contact,
            'telephone' => $this->telephone,
            'telephone_secondaire' => $this->telephone_secondaire,
            'email' => $this->email,
            'site_web' => $this->site_web,
            'adresse' => $this->adresse,
            'ville' => $this->ville,
            'pays' => $this->pays,
            'pays_nom' => $this->pays_nom,
            'compte_comptable_id' => $this->compte_comptable_id,
            'comptes_supplementaires' => $this->comptes_supplementaires_ids,
            'compte_comptable' => $this->compteComptable ? [
                'id' => $this->compteComptable->id,
                'numero' => $this->compteComptable->numero_compte,
                'libelle' => $this->compteComptable->libelle,
            ] : null,
            'ifu' => $this->ifu,
            'rccm' => $this->rccm,
            'observations' => $this->observations,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
