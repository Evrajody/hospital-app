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
 * @property string $status
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
 * @property string|null $regime_fiscal
 * @property string $taux_aib
 * @property bool $assujetti_tva
 * @property \Carbon\Carbon|null $date_debut_relation
 * @property int $delai_paiement
 * @property string $mode_paiement_prefere
 * @property float|null $plafond_credit
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
        'status',
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
        'regime_fiscal',
        'taux_aib',
        'assujetti_tva',
        'date_debut_relation',
        'delai_paiement',
        'mode_paiement_prefere',
        'plafond_credit',
        'observations',
    ];

    /**
     * Les attributs castés
     */
    protected $casts = [
        'assujetti_tva' => 'boolean',
        'date_debut_relation' => 'date',
        'delai_paiement' => 'integer',
        'plafond_credit' => 'decimal:2',
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

    /**
     * Constantes pour les régimes fiscaux
     */
    const REGIME_REEL_NORMAL = 'reel_normal';
    const REGIME_REEL_SIMPLIFIE = 'reel_simplifie';
    const REGIME_MICRO = 'micro';
    const REGIME_EXONERE = 'exonere';

    /**
     * Constantes pour les modes de paiement
     */
    const PAIEMENT_VIREMENT = 'virement';
    const PAIEMENT_CHEQUE = 'cheque';
    const PAIEMENT_ESPECES = 'especes';
    const PAIEMENT_MOBILE_MONEY = 'mobile_money';

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * Relation avec le compte comptable
     */
    public function compteComptable(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_comptable_id');
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
     * Filtrer par statut
     */
    public function scopeActif(Builder $query): Builder
    {
        return $query->where('status', 'actif');
    }

    /**
     * Filtrer par statut inactif
     */
    public function scopeInactif(Builder $query): Builder
    {
        return $query->where('status', 'inactif');
    }

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
     * Obtenir le libellé du régime fiscal
     */
    public function getRegimeFiscalLibelleAttribute(): string
    {
        return match($this->regime_fiscal) {
            self::REGIME_REEL_NORMAL => 'Réel Normal',
            self::REGIME_REEL_SIMPLIFIE => 'Réel Simplifié',
            self::REGIME_MICRO => 'Micro-entreprise',
            self::REGIME_EXONERE => 'Exonéré',
            default => 'Non défini',
        };
    }

    /**
     * Obtenir le libellé du mode de paiement préféré
     */
    public function getModePaiementLibelleAttribute(): string
    {
        return match($this->mode_paiement_prefere) {
            self::PAIEMENT_VIREMENT => 'Virement bancaire',
            self::PAIEMENT_CHEQUE => 'Chèque',
            self::PAIEMENT_ESPECES => 'Espèces',
            self::PAIEMENT_MOBILE_MONEY => 'Mobile Money',
            default => 'Non défini',
        };
    }

    /**
     * Obtenir le libellé du taux AIB
     */
    public function getTauxAibLibelleAttribute(): string
    {
        return match($this->taux_aib) {
            '0' => '0% - Exonéré',
            '1' => '1% - Standard',
            '3' => '3% - Sans IFU',
            '5' => '5% - Importateurs',
            default => $this->taux_aib . '%',
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
        $actifs = self::actif()->count();
        $inactifs = self::inactif()->count();

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
            'actifs' => $actifs,
            'inactifs' => $inactifs,
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

    /**
     * Obtenir les régimes fiscaux
     */
    public static function getRegimesFiscaux(): array
    {
        return [
            ['value' => self::REGIME_REEL_NORMAL, 'label' => 'Réel Normal'],
            ['value' => self::REGIME_REEL_SIMPLIFIE, 'label' => 'Réel Simplifié'],
            ['value' => self::REGIME_MICRO, 'label' => 'Micro-entreprise'],
            ['value' => self::REGIME_EXONERE, 'label' => 'Exonéré'],
        ];
    }

    /**
     * Obtenir les modes de paiement
     */
    public static function getModesPaiement(): array
    {
        return [
            ['value' => self::PAIEMENT_VIREMENT, 'label' => 'Virement bancaire'],
            ['value' => self::PAIEMENT_CHEQUE, 'label' => 'Chèque'],
            ['value' => self::PAIEMENT_ESPECES, 'label' => 'Espèces'],
            ['value' => self::PAIEMENT_MOBILE_MONEY, 'label' => 'Mobile Money'],
        ];
    }

    /**
     * Obtenir les taux AIB disponibles
     */
    public static function getTauxAib(): array
    {
        return [
            ['value' => '0', 'label' => '0% - Exonéré'],
            ['value' => '1', 'label' => '1% - Standard'],
            ['value' => '3', 'label' => '3% - Sans IFU'],
            ['value' => '5', 'label' => '5% - Importateurs'],
        ];
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    /**
     * Vérifier si le fournisseur est actif
     */
    public function estActif(): bool
    {
        return $this->status === 'actif';
    }

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
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'type_fournisseur' => $this->type_fournisseur,
            'type_fournisseur_libelle' => $this->type_fournisseur_libelle,
            'status' => $this->status,
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
            'compte_comptable' => $this->compteComptable ? [
                'id' => $this->compteComptable->id,
                'numero' => $this->compteComptable->numero_compte,
                'libelle' => $this->compteComptable->libelle,
            ] : null,
            'ifu' => $this->ifu,
            'rccm' => $this->rccm,
            'regime_fiscal' => $this->regime_fiscal,
            'regime_fiscal_libelle' => $this->regime_fiscal_libelle,
            'taux_aib' => $this->taux_aib,
            'taux_aib_libelle' => $this->taux_aib_libelle,
            'assujetti_tva' => $this->assujetti_tva,
            'date_debut_relation' => $this->date_debut_relation?->format('Y-m-d'),
            'delai_paiement' => $this->delai_paiement,
            'mode_paiement_prefere' => $this->mode_paiement_prefere,
            'mode_paiement_libelle' => $this->mode_paiement_libelle,
            'plafond_credit' => $this->plafond_credit,
            'observations' => $this->observations,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
