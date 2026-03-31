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
        'institution',
        'reference_cheque',
        'banque_depot_id',
        'compte_bancaire_id',
        'observations',
        'created_by',
        'created_by_name',
    ];

    const TYPE_REGLEMENT = 'reglement';
    const TYPE_PERTE = 'perte';
    const TYPE_REJET = 'rejet';
    const TYPE_REGULARISATION = 'regularisation';

    public static function getTypesReglement(): array
    {
        return [
            ['value' => self::TYPE_REGLEMENT, 'label' => 'Règlement'],
            ['value' => self::TYPE_PERTE, 'label' => 'Perte'],
            ['value' => self::TYPE_REJET, 'label' => 'Rejet'],
            ['value' => self::TYPE_REGULARISATION, 'label' => 'Régularisation'],
        ];
    }

    public function getTypeReglementLibelleAttribute(): string
    {
        return match($this->type_reglement) {
            self::TYPE_PERTE => 'Perte',
            self::TYPE_REJET => 'Rejet',
            self::TYPE_REGULARISATION => 'Régularisation',
            default => 'Règlement',
        };
    }

    public function getTypeReglementCouleurAttribute(): string
    {
        return match($this->type_reglement) {
            self::TYPE_PERTE => 'danger',
            self::TYPE_REJET => 'warning',
            self::TYPE_REGULARISATION => 'success',
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

    public function compteBancaire(): BelongsTo
    {
        return $this->belongsTo(CompteBancaire::class, 'compte_bancaire_id');
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
            ],
            'client_id' => $this->client_id,
            'client' => [
                'id' => $this->client_id,
                'nom' => $this->client_nom ?: $this->client?->nom,
            ],
            'montant' => (float) $this->montant,
            'institution' => $this->institution,
            'reference_cheque' => $this->reference_cheque,
            'banque_depot_id' => $this->banque_depot_id,
            'banque_depot' => $this->banqueDepot ? [
                'id' => $this->banqueDepot->id,
                'nom' => $this->banqueDepot->nom,
            ] : null,
            'compte_bancaire_id' => $this->compte_bancaire_id,
            'compte_bancaire' => $this->compteBancaire ? [
                'id' => $this->compteBancaire->id,
                'numero_compte' => $this->compteBancaire->numero_compte,
            ] : null,
            'observations' => $this->observations,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
