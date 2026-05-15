<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ReglementClient extends Model
{
    use SoftDeletes;

    protected $table = 'reglements_clients';

    protected $fillable = [
        'numero_ligne',
        'type_reglement',
        'date_reglement',
        'facture_id',
        'client_id',
        'client_nom',
        'facture_reference',
        'montant',
        'montant_rejet',
        'institution',
        'reference_cheque',
        'banque_depot_id',
        'approvisionnement_id',
        'observations',
        'bordereau_depot_path',
        'created_by',
        'created_by_name',
    ];

    const TYPE_REGLEMENT = 'reglement';
    const TYPE_PERTE = 'perte';

    public static function getTypesReglement(): array
    {
        return [
            ['value' => self::TYPE_REGLEMENT, 'label' => 'Règlement'],
            ['value' => self::TYPE_PERTE, 'label' => 'Perte'],
        ];
    }

    public function getTypeReglementLibelleAttribute(): string
    {
        return match($this->type_reglement) {
            self::TYPE_PERTE => 'Perte',
            default => 'Règlement',
        };
    }

    public function getTypeReglementCouleurAttribute(): string
    {
        return match($this->type_reglement) {
            self::TYPE_PERTE => 'danger',
            default => 'primary',
        };
    }

    protected $casts = [
        'date_reglement' => 'date',
        'montant' => 'decimal:2',
    ];

    // ==========================================
    // RELATIONS
    // ==========================================

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureClient::class, 'facture_id');
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function banqueDepot(): BelongsTo
    {
        return $this->belongsTo(Banque::class, 'banque_depot_id');
    }

    public function approvisionnement(): BelongsTo
    {
        return $this->belongsTo(ApprovisionnementBanque::class, 'approvisionnement_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Récupérer la liste des institutions déjà utilisées
     */
    public static function getInstitutions(): array
    {
        return self::whereNotNull('institution')
            ->where('institution', '!=', '')
            ->distinct()
            ->orderBy('institution')
            ->pluck('institution')
            ->toArray();
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'numero_ligne' => $this->numero_ligne,
            'type_reglement' => $this->type_reglement ?? 'reglement',
            'type_reglement_libelle' => $this->type_reglement_libelle,
            'type_reglement_couleur' => $this->type_reglement_couleur,
            'date_reglement' => $this->date_reglement?->format('Y-m-d'),
            'facture_id' => $this->facture_id,
            'facture' => [
                'id' => $this->facture_id,
                'reference' => $this->facture_reference ?: $this->facture?->reference,
                'date_facture' => $this->facture?->date_facture?->format('Y-m-d'),
                'libelle' => $this->facture?->libelle,
                'montant' => (float) ($this->facture?->montant ?? 0),
                'montant_paye' => (float) ($this->facture?->montant_paye ?? 0),
                'reste_a_payer' => (float) ($this->facture?->reste_a_payer ?? 0),
            ],
            'client_id' => $this->client_id,
            'client' => [
                'id' => $this->client_id,
                'nom' => $this->client_nom ?: $this->client?->nom,
            ],
            'montant' => (float) $this->montant,
            'montant_rejet' => (float) ($this->montant_rejet ?? 0),
            'institution' => $this->institution,
            'reference_cheque' => $this->reference_cheque,
            'banque_depot_id' => $this->banque_depot_id,
            'banque_depot' => $this->banqueDepot ? [
                'id' => $this->banqueDepot->id,
                'nom' => $this->banqueDepot->nom,
            ] : null,
            'approvisionnement_id' => $this->approvisionnement_id,
            'approvisionnement' => $this->approvisionnement ? [
                'id' => $this->approvisionnement->id,
                'reference_bordereau' => $this->approvisionnement->reference_bordereau,
                'date_depot' => $this->approvisionnement->date_depot?->format('Y-m-d'),
                'compte_bancaire_id' => $this->approvisionnement->compte_bancaire_id,
            ] : null,
            'observations' => $this->observations,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
