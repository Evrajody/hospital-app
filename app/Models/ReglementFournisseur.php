<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\DB;

/**
 * Modèle ReglementFournisseur
 * Gestion des paiements des factures fournisseurs
 */
class ReglementFournisseur extends Model
{
    use SoftDeletes;

    /**
     * Nom de la table
     */
    protected $table = 'reglements_fournisseurs';

    /**
     * Les attributs mass assignable
     */
    protected $fillable = [
        'numero_reglement',
        'date_reglement',
        'facture_id',
        'fournisseur_id',
        'fournisseur_nom',
        'facture_numero',
        'montant',
        'mode_paiement',
        'reference',
        'beneficiaire',
        'banque',
        'numero_compte_bancaire',
        'compte_tresorerie_id',
        'compte_credit_id',
        'observations',
        'deduire_aib',
        'montant_aib_deduit',
        'compte_aib',
        'date_aib',
        'statut',
        'created_by',
        'created_by_name',
        'validated_by',
        'validated_by_name',
        'validated_at',
        'etablissement_nom',
        'etablissement_directeur',
        'etablissement_entete_bp',
        'etablissement_entete_tel',
        'etablissement_entete_email',
        'etablissement_entete_site',
    ];

    /**
     * Les attributs castés
     */
    protected $casts = [
        'date_reglement' => 'date',
        'validated_at' => 'datetime',
        'montant' => 'decimal:2',
        'montant_aib_deduit' => 'decimal:2',
        'deduire_aib' => 'boolean',
        'date_aib' => 'date',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    /**
     * Constantes pour les modes de paiement
     */
    const MODE_VIREMENT = 'virement';
    const MODE_CHEQUE = 'cheque';
    const MODE_ESPECES = 'especes';
    const MODE_MOBILE_MONEY = 'mobile_money';
    const MODE_CARTE = 'carte';

    /**
     * Constantes pour les statuts
     */
    const STATUT_EN_ATTENTE = 'en_attente';
    const STATUT_VALIDE = 'valide';
    const STATUT_ANNULE = 'annule';

    // ==========================================
    // RELATIONS
    // ==========================================

    /**
     * Relation avec la facture
     */
    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_id');
    }

    /**
     * Relation avec le fournisseur
     */
    public function fournisseur(): BelongsTo
    {
        return $this->belongsTo(Fournisseur::class);
    }

    /**
     * Relation avec le compte de trésorerie
     */
    public function compteTresorerie(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_tresorerie_id');
    }

    /**
     * Relation avec le compte fournisseur (401/481) débité sur ce règlement (legacy mono-compte)
     */
    public function compteCredit(): BelongsTo
    {
        return $this->belongsTo(CompteComptable::class, 'compte_credit_id');
    }

    /**
     * Lignes du règlement (multi-fournisseur)
     */
    public function lignes(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(ReglementFournisseurLigne::class, 'reglement_id');
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
     * Règlements validés
     */
    public function scopeValide(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_VALIDE);
    }

    /**
     * Règlements en attente
     */
    public function scopeEnAttente(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_EN_ATTENTE);
    }

    /**
     * Règlements annulés
     */
    public function scopeAnnule(Builder $query): Builder
    {
        return $query->where('statut', self::STATUT_ANNULE);
    }

    /**
     * Règlements par mode de paiement
     */
    public function scopeParMode(Builder $query, string $mode): Builder
    {
        return $query->where('mode_paiement', $mode);
    }

    /**
     * Règlements d'un fournisseur
     */
    public function scopeDuFournisseur(Builder $query, int $fournisseurId): Builder
    {
        return $query->where('fournisseur_id', $fournisseurId);
    }

    /**
     * Règlements d'une facture
     */
    public function scopeDeFacture(Builder $query, int $factureId): Builder
    {
        return $query->where('facture_id', $factureId);
    }

    /**
     * Règlements sur une période
     */
    public function scopePeriode(Builder $query, string $debut, string $fin): Builder
    {
        return $query->whereBetween('date_reglement', [$debut, $fin]);
    }

    /**
     * Recherche globale
     */
    public function scopeRecherche(Builder $query, string $terme): Builder
    {
        return $query->where(function ($q) use ($terme) {
            $q->where('numero_reglement', 'ILIKE', "%{$terme}%")
              ->orWhere('reference', 'ILIKE', "%{$terme}%")
              ->orWhereHas('fournisseur', function ($q2) use ($terme) {
                  $q2->where('nom', 'ILIKE', "%{$terme}%");
              })
              ->orWhereHas('facture', function ($q2) use ($terme) {
                  $q2->where('numero_piece', 'ILIKE', "%{$terme}%");
              });
        });
    }

    // ==========================================
    // ACCESSORS
    // ==========================================

    /**
     * Obtenir le libellé du mode de paiement
     */
    public function getModePaiementLibelleAttribute(): string
    {
        return match($this->mode_paiement) {
            self::MODE_VIREMENT => 'Virement bancaire',
            self::MODE_CHEQUE => 'Chèque',
            self::MODE_ESPECES => 'Espèces',
            self::MODE_MOBILE_MONEY => 'Mobile Money',
            self::MODE_CARTE => 'Carte bancaire',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir le libellé du statut
     */
    public function getStatutLibelleAttribute(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'En attente',
            self::STATUT_VALIDE => 'Validé',
            self::STATUT_ANNULE => 'Annulé',
            default => 'Inconnu',
        };
    }

    /**
     * Obtenir la couleur du statut
     */
    public function getStatutCouleurAttribute(): string
    {
        return match($this->statut) {
            self::STATUT_EN_ATTENTE => 'warning',
            self::STATUT_VALIDE => 'success',
            self::STATUT_ANNULE => 'danger',
            default => 'info',
        };
    }

    /**
     * Vérifier si le règlement est modifiable
     */
    public function getEstModifiableAttribute(): bool
    {
        return $this->statut !== self::STATUT_ANNULE;
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    /**
     * Valider le règlement
     */
    public function valider(?int $userId = null): bool
    {
        if ($this->statut === self::STATUT_ANNULE) {
            return false;
        }

        $this->statut = self::STATUT_VALIDE;
        $this->validated_by = $userId;
        $this->validated_at = now();

        return $this->save();
    }

    /**
     * Annuler le règlement
     */
    public function annuler(): bool
    {
        if ($this->statut === self::STATUT_ANNULE) {
            return false;
        }

        DB::beginTransaction();

        try {
            // Remettre le montant sur la facture
            $facture = $this->facture;
            if ($facture) {
                // Convertir en float pour éviter les problèmes de type decimal/string
                $montantReglement = (float) $this->montant;
                $montantPaye = (float) $facture->montant_paye - $montantReglement;
                $montantNet = (float) $facture->montant_net;

                $facture->montant_paye = max(0, $montantPaye);
                $facture->reste_a_payer = $montantNet - $facture->montant_paye;

                // Mettre à jour le statut de la facture (tolérance 0.01 pour arrondi)
                if ($facture->montant_paye <= 0.01) {
                    $facture->statut = FactureFournisseur::STATUT_VALIDEE;
                    $facture->montant_paye = 0;
                } else {
                    $facture->statut = FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE;
                }

                $facture->save();
            }

            $this->statut = self::STATUT_ANNULE;
            $this->save();

            DB::commit();
            return true;

        } catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Générer un numéro de règlement unique
     */
    public static function genererNumeroReglement(): string
    {
        $annee = date('Y');
        $prefixe = 'REG/' . substr($annee, -2);

        // Trouver le dernier numéro
        $dernierNumero = self::where('numero_reglement', 'LIKE', $prefixe . '/%')
            ->orderBy('numero_reglement', 'desc')
            ->value('numero_reglement');

        if ($dernierNumero) {
            $parties = explode('/', $dernierNumero);
            $sequence = (int) end($parties) + 1;
        } else {
            $sequence = 1;
        }

        return $prefixe . '/' . str_pad($sequence, 5, '0', STR_PAD_LEFT);
    }

    /**
     * Obtenir les modes de paiement disponibles
     */
    public static function getModesPaiement(): array
    {
        return [
            ['value' => self::MODE_VIREMENT, 'label' => 'Virement bancaire'],
            ['value' => self::MODE_CHEQUE, 'label' => 'Chèque'],
            ['value' => self::MODE_ESPECES, 'label' => 'Espèces'],
            ['value' => self::MODE_MOBILE_MONEY, 'label' => 'Mobile Money'],
            ['value' => self::MODE_CARTE, 'label' => 'Carte bancaire'],
        ];
    }

    /**
     * Obtenir les statistiques globales
     */
    public static function getStatistiques(?int $fournisseurId = null): array
    {
        $query = self::valide();

        if ($fournisseurId) {
            $query->where('fournisseur_id', $fournisseurId);
        }

        $total = $query->sum('montant');
        $nombreReglements = $query->count();

        // Règlements du mois en cours
        $debutMois = now()->startOfMonth()->format('Y-m-d');
        $finMois = now()->endOfMonth()->format('Y-m-d');

        $queryMois = self::valide()->periode($debutMois, $finMois);
        if ($fournisseurId) {
            $queryMois->where('fournisseur_id', $fournisseurId);
        }
        $totalMois = $queryMois->sum('montant');

        return [
            'total_reglements' => (float) $total,
            'reglements_mois' => (float) $totalMois,
            'nombre_reglements' => $nombreReglements,
            'montant_moyen' => $nombreReglements > 0 ? $total / $nombreReglements : 0,
        ];
    }

    /**
     * Convertir en tableau pour l'API
     */
    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'numero_reglement' => $this->numero_reglement,
            'date_reglement' => $this->date_reglement?->format('Y-m-d'),
            'facture_id' => $this->facture_id,
            'facture' => [
                'id' => $this->facture_id,
                'numero' => $this->facture_numero ?: $this->facture?->numero_piece,
                'numero_piece' => $this->facture_numero ?: $this->facture?->numero_piece,
                'date' => $this->facture?->date?->format('Y-m-d'),
                'date_facture' => $this->facture?->date?->format('Y-m-d'),
                'libelle' => $this->facture?->libelle,
                'montant_ttc' => (float) ($this->facture?->montant_ttc ?? 0),
                'montant_net' => (float) ($this->facture?->montant_net ?? 0),
                'montant_paye' => (float) ($this->facture?->montant_paye ?? 0),
                'reste_a_payer' => (float) ($this->facture?->reste_a_payer ?? 0),
            ],
            'fournisseur_id' => $this->fournisseur_id,
            'fournisseur' => [
                'id' => $this->fournisseur_id,
                'code' => 'FOUR' . str_pad($this->fournisseur_id, 3, '0', STR_PAD_LEFT),
                'nom' => $this->fournisseur_nom ?: $this->fournisseur?->nom,
            ],
            'montant' => (float) $this->montant,
            'mode_paiement' => $this->mode_paiement,
            'mode_paiement_libelle' => $this->mode_paiement_libelle,
            'reference' => $this->reference,
            'beneficiaire' => $this->beneficiaire,
            'banque' => $this->banque,
            'numero_compte_bancaire' => $this->numero_compte_bancaire,
            'compte_bancaire' => $this->banque ? [
                'banque' => $this->banque,
                'numero' => $this->numero_compte_bancaire,
            ] : null,
            'compte_tresorerie_id' => $this->compte_tresorerie_id,
            'compte_tresorerie' => $this->compteTresorerie ? [
                'id' => $this->compteTresorerie->id,
                'numero' => $this->compteTresorerie->numero_compte,
                'libelle' => $this->compteTresorerie->libelle,
            ] : null,
            'compte_credit_id' => $this->compte_credit_id,
            'compte_credit' => $this->compteCredit ? [
                'id' => $this->compteCredit->id,
                'numero' => $this->compteCredit->numero_compte,
                'libelle' => $this->compteCredit->libelle,
            ] : null,
            'lignes' => $this->relationLoaded('lignes')
                ? $this->lignes->map(fn($l) => [
                    'id' => $l->id,
                    'compte_id' => $l->compte_id,
                    'numero_compte' => $l->compte?->numero_compte,
                    'libelle_compte' => $l->compte?->libelle,
                    'libelle' => $l->libelle,
                    'montant' => (float) $l->montant,
                ])->values()->toArray()
                : [],
            'observations' => $this->observations,
            'deduire_aib' => (bool) $this->deduire_aib,
            'montant_aib_deduit' => (float) $this->montant_aib_deduit,
            'compte_aib' => $this->compte_aib,
            'statut' => $this->statut,
            'statut_libelle' => $this->statut_libelle,
            'statut_couleur' => $this->statut_couleur,
            'est_modifiable' => $this->est_modifiable,
            'user' => [
                'name' => $this->created_by_name ?: $this->createur?->name,
            ],
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
