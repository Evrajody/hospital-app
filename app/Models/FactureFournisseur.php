<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\DB;

/**
 * Modèle FactureFournisseur
 * Gestion des factures reçues des fournisseurs
 *
 * @property-read \Illuminate\Support\Carbon|null $date            Date PC : enregistrement dans le système
 * @property-read \Illuminate\Support\Carbon|null $date_facture_bc Date facture / BC : inscrite par le fournisseur
 * @property-read \Illuminate\Support\Carbon|null $date_solde      Date de règlement : dernier règlement ou marquage manuel
 */
class FactureFournisseur extends Model
{
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'factures_fournisseurs';

    /**
     * Les attributs mass assignable
     */
    protected $fillable = [
        'numero_piece',
        'date',
        'reference_facture',
        'fournisseur_id',
        'fournisseur_nom',
        'imputation_id',
        'compte_id',
        'libelle',
        'montant_facture',
        'montant_mo',
        'avoir',
        'type_reduction',
        'taux',
        'montant_reduction',
        'assujetti_tva',
        'taux_tva',
        'montant_tva',
        'montant_ht',
        'montant_ttc',
        'montant_net',
        'statut',
        'montant_paye',
        'reste_a_payer',
        'date_solde',
        'date_facture_bc',
        'observations',
        'metadata',
        'created_by',
        'created_by_name',
        'validated_by',
        'validated_by_name',
        'validated_at',
    ];

    /**
     * Les attributs castés
     */
    protected $casts = [
        'date' => 'date',
        'date_facture_bc' => 'date',
        'validated_at' => 'datetime',
        'montant_facture' => 'decimal:2',
        'montant_mo' => 'decimal:2',
        'avoir' => 'decimal:2',
        'taux' => 'decimal:2',
        'montant_reduction' => 'decimal:2',
        'taux_tva' => 'decimal:2',
        'montant_tva' => 'decimal:2',
        'montant_ht' => 'decimal:2',
        'montant_ttc' => 'decimal:2',
        'montant_net' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
        'date_solde' => 'date',
        'assujetti_tva' => 'boolean',
        'metadata' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Constantes pour les statuts
     */
    const STATUT_BROUILLON = 'brouillon';
    const STATUT_VALIDEE = 'validee';
    const STATUT_PARTIELLEMENT_PAYEE = 'partiellement_payee';
    const STATUT_PAYEE = 'payee';
    const STATUT_ANNULEE = 'annulee';

    /**
     * Constantes pour les types de réduction (AIB)
     */
    const REDUCTION_AIB = 'aib';

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * Relation avec le fournisseur
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementFournisseur::class, 'facture_id');
    }

    /**
     * Relation avec l'imputation (classe comptable)
     */
    public function imputation(): BelongsTo
    {
        return $this->belongsTo(Classe::class, 'imputation_id');
    }

    /**
     * Relation avec le compte comptable
     */
    public function compte(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_id');
    }

    /**
     * Imputations multiples (facture → N lignes compte/montant).
     */
    public function imputations()
    {
        return $this->hasMany(ImputationFactureFournisseur::class, 'facture_id');
    }

    /**
     * Relation avec l'utilisateur créateur
     */
    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relation avec l'utilisateur validateur
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validated_by');
    }

    // ==========================================
    // SCOPES
    // ==========================================

    /**
     * Factures en brouillon
     */
    public function scopeBrouillon(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_BROUILLON);
    }

    /**
     * Factures validées
     */
    public function scopeValidee(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_VALIDEE);
    }

    /**
     * Factures payées
     */
    public function scopePayee(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PAYEE);
    }

    /**
     * Factures partiellement payées
     */
    public function scopePartiellementPayee(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_PARTIELLEMENT_PAYEE);
    }

    /**
     * Factures non payées (validées ou partiellement payées)
     */
    public function scopeNonPayee(Builder $query): Builder
    {
        return $query->whereIn('statut', [self::STATUT_VALIDEE, self::STATUT_PARTIELLEMENT_PAYEE]);
    }

    /**
     * Factures annulées
     */
    public function scopeAnnulee(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_ANNULEE);
    }

    /**
     * Factures d'un fournisseur
     */
    public function scopeDuFournisseur(Builder $query, int $fournisseurId): Builder
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    /**
     * Factures d'une période
     */
    public function scopePeriode(Builder $query, string $debut, string $fin): Builder
    {
        return $query->whereBetween('date', [$debut, $fin]);
    }

    /**
     * Recherche globale
     */
    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('numero_piece', 'ILIKE', "%{$terme}%")
              ->orWhere('reference_facture', 'ILIKE', "%{$terme}%")
              ->orWhere('libelle', 'ILIKE', "%{$terme}%")
              ->orWhereHas('fournisseur', function ($q2) use ($terme) {
                  $q2->where('nom', 'ILIKE', "%{$terme}%");
              });
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            self::STATUT_BROUILLON => 'Brouillon',
            self::STATUT_VALIDEE => 'Validée',
            self::STATUT_PARTIELLEMENT_PAYEE => 'Partiellement payée',
            self::STATUT_PAYEE => 'Payée',
            self::STATUT_ANNULEE => 'Annulée',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la couleur du statut pour l'affichage
     */
    public function getStatutCouleurAttribute(): string
    {
        return match($this->statut) {
            self::STATUT_BROUILLON => 'info',
            self::STATUT_VALIDEE => 'warning',
            self::STATUT_PARTIELLEMENT_PAYEE => 'primary',
            self::STATUT_PAYEE => 'success',
            self::STATUT_ANNULEE => 'danger',
            default => 'info',
        };
    }

    /**
     * Obtenir le libellé du type de réduction
     */
    public function getTypeReductionLibelleAttribute(): ?string
    {
        if (!$this->type_reduction) return null;

        // Le type_reduction stocke le code du compte AIB (ex: '4473', '447310')
        $compte = \App\Models\CompteComptable::where('numero_compte', $this->type_reduction)->first();
        if ($compte) {
            return $compte->numero_compte . ' - ' . $compte->libelle;
        }

        return $this->type_reduction;
    }

    /**
     * Une facture est toujours modifiable (pas d'étape brouillon/validation).
     */
    public function getEstModifiableAttribute(): bool
    {
        return true;
    }

    /**
     * Vérifier si la facture peut être payée
     */
    public function getPeutEtrePayeeAttribute(): bool
    {
        return in_array($this->statut, [self::STATUT_BROUILLON, self::STATUT_VALIDEE, self::STATUT_PARTIELLEMENT_PAYEE]);
    }

    // ==========================================
    // MÉTHODES DE CALCUL
    // ==========================================

    /**
     * Calculer les montants automatiquement avant la sauvegarde
     */
    public function calculerMontants(): void
    {
        // Montant HT = Montant Facture (pas de soustraction de l'avoir)
        $this->montant_ht = $this->montant_facture;

        // TVA calculée sur le HT (informative, versée par l'entreprise)
        if ($this->assujetti_tva && $this->taux_tva > 0) {
            $this->montant_tva = round(($this->montant_ht * $this->taux_tva) / 100, 2);
        } else {
            $this->montant_tva = 0;
        }

        // TTC (pour référence)
        $this->montant_ttc = round($this->montant_ht + $this->montant_tva, 2);

        // AIB calculé sur le Montant M.O.
        if ($this->montant_mo > 0 && $this->taux > 0) {
            $this->montant_reduction = round(($this->montant_mo * $this->taux) / 100, 2);
        } else {
            $this->montant_reduction = 0;
        }

        // NAP (Net à Payer) = TTC − TVA − Avoir − AIB = HT − Avoir − AIB
        // Modèle "TVA pour compte" : le fournisseur n'encaisse que le HT,
        // la TVA reste pour le compte de l'État.
        $this->montant_net = round($this->montant_facture - $this->avoir - $this->montant_reduction, 2);

        // Reste à payer
        $this->reste_a_payer = round($this->montant_net - $this->montant_paye, 2);
    }

    /**
     * Boot du modèle - Calcul automatique avant sauvegarde
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($facture) {
            $facture->calculerMontants();
        });
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    /**
     * Annuler la facture
     */
    public function annuler(): bool
    {
        if ($this->statut === self::STATUT_PAYEE) {
            return false;
        }

        $this->statut = self::STATUT_ANNULEE;

        return $this->save();
    }

    /**
     * Enregistrer un paiement
     */
    public function enregistrerPaiement(float $montant): bool
    {
        if (!$this->peut_etre_payee) {
            return false;
        }

        // Convertir explicitement en float pour éviter les problèmes de comparaison decimal/string
        $montantPaye = round((float) $this->montant_paye + $montant, 2);
        $montantNet = (float) $this->montant_net;

        $this->montant_paye = $montantPaye;
        $this->reste_a_payer = round($montantNet - $montantPaye, 2);

        // Tolérance de 0.01 pour les erreurs d'arrondi
        if ($this->reste_a_payer <= 0.01) {
            $this->statut = self::STATUT_PAYEE;
            $this->reste_a_payer = 0;
            $this->date_solde = now();
        } else {
            $this->statut = self::STATUT_PARTIELLEMENT_PAYEE;
        }

        return $this->save();
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Générer un nouveau numéro de pièce
     */
    public static function genererNumeroPiece(): string
    {
        $annee = date('Y');
        $prefixe = 'PC/' . substr($annee, -3);

        // Trouver le dernier numéro
        $dernierNumero = self::where('numero_piece', 'LIKE', $prefixe . '/%')
            ->orderBy('numero_piece', 'desc')
            ->value('numero_piece');

        if ($dernierNumero) {
            $parties = explode('/', $dernierNumero);
            $sequence = (int) end($parties) + 1;
        } else {
            $sequence = 1;
        }

        return $prefixe . '/' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Obtenir les statistiques globales
     */
    public static function getStatistiques(?int $fournisseurId = null): array
    {
        $query = self::query();

        if ($fournisseurId) {
            $query->where('fournisseur_id', $fournisseurId);
        }

        $total = $query->count();
        $brouillon = (clone $query)->brouillon()->count();
        $validees = (clone $query)->validee()->count();
        $payees = (clone $query)->payee()->count();
        $enCours = (clone $query)->nonPayee()->count();

        $montantTotal = (clone $query)->sum('montant_net');
        $montantPaye = (clone $query)->sum('montant_paye');
        $montantRestant = $montantTotal - $montantPaye;

        return [
            'total' => $total,
            'brouillon' => $brouillon,
            'validees' => $validees,
            'payees' => $payees,
            'en_cours' => $enCours,
            'montant_total' => $montantTotal,
            'montant_paye' => $montantPaye,
            'montant_restant' => $montantRestant,
        ];
    }

    /**
     * Obtenir les comptes AIB disponibles
     */
    public static function getComptesAib(): array
    {
        return \App\Models\CompteComptable::where('numero_compte', 'LIKE', '4473%')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'code' => $c->numero_compte,
                'libelle' => $c->libelle,
            ])
            ->toArray();
    }

    /**
     * Obtenir les statuts disponibles
     */
    public static function getStatuts(): array
    {
        return [
            ['value' => self::STATUT_BROUILLON, 'label' => 'Brouillon', 'color' => 'info'],
            ['value' => self::STATUT_VALIDEE, 'label' => 'Validée', 'color' => 'warning'],
            ['value' => self::STATUT_PARTIELLEMENT_PAYEE, 'label' => 'Partiellement payée', 'color' => 'primary'],
            ['value' => self::STATUT_PAYEE, 'label' => 'Payée', 'color' => 'success'],
            ['value' => self::STATUT_ANNULEE, 'label' => 'Annulée', 'color' => 'danger'],
        ];
    }

    /**
     * Convertir en tableau pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'numero_piece' => $this->numero_piece,
            'date' => $this->date?->format('Y-m-d'),
            'reference_facture' => $this->reference_facture,
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => [
                'id' => $this->fournisseur_id,
                'nom' => $this->fournisseur_nom ?: $this->fournisseur?->nom,
            ],
            'imputation_id' => $this->imputation_id,
            'imputation' => $this->imputation ? [
                'id' => $this->imputation->id,
                'code' => $this->imputation->numero_compte ?? $this->imputation->numero,
                'libelle' => $this->imputation->libelle,
            ] : null,
            'compte_id' => $this->compte_id,
            'compte' => $this->compte ? [
                'id' => $this->compte->id,
                'numero' => $this->compte->numero_compte ?? $this->compte->numero,
                'libelle' => $this->compte->libelle,
            ] : null,
            'libelle' => $this->libelle,
            'montant_facture' => $this->montant_facture,
            'montant_mo' => $this->montant_mo,
            'avoir' => $this->avoir,
            'type_reduction' => $this->type_reduction,
            'type_reduction_libelle' => $this->type_reduction_libelle,
            'taux' => $this->taux,
            'montant_reduction' => $this->montant_reduction,
            'assujetti_tva' => $this->assujetti_tva,
            'taux_tva' => $this->taux_tva,
            'montant_tva' => $this->montant_tva,
            'montant_ht' => $this->montant_ht,
            'montant_ttc' => $this->montant_ttc,
            'montant_net' => $this->montant_net,
            'statut' => $this->statut,
            'statut_libelle' => $this->statut_libelle,
            'statut_couleur' => $this->statut_couleur,
            'montant_paye' => $this->montant_paye,
            'reste_a_payer' => $this->reste_a_payer,
            'date_facture_bc' => $this->date_facture_bc?->format('Y-m-d'),
            'date_solde' => $this->date_solde?->format('Y-m-d'),
            'observations' => $this->observations,
            'metadata' => $this->metadata,
            'est_modifiable' => $this->est_modifiable,
            'peut_etre_payee' => $this->peut_etre_payee,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
            'imputations' => $this->relationLoaded('imputations')
                ? $this->imputations->map(fn($imp) => [
                    'id' => $imp->id,
                    'compte_id' => $imp->compte_id,
                    'nature' => $imp->nature ?? 'debit',
                    'libelle' => $imp->libelle,
                    'montant' => (float) $imp->montant,
                ])->values()->toArray()
                : [],
        ];
    }
}
