<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class AvanceClient extends Model
{
    use SoftDeletes;

    protected $table = 'avances_clients';

    protected $fillable = [
        'numero_ligne',
        'client_id',
        'client_nom',
        'societe_emettrice',
        'numero_cheque',
        'date_cheque',
        'montant',
        'montant_utilise',
        'numero_proforma',
        'statut',
        'institution',
        'banque_depot_id',
        'approvisionnement_id',
        'bordereau_depot_path',
        'observations',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'date_cheque' => 'date',
        'montant' => 'decimal:2',
        'montant_utilise' => 'decimal:2',
    ];

    const STATUT_DISPONIBLE = 'disponible';
    const STATUT_PARTIELLEMENT_UTILISEE = 'partiellement_utilisee';
    const STATUT_EPUISEE = 'epuisee';

    public static function getStatutsLabels(): array
    {
        return [
            self::STATUT_DISPONIBLE => 'Disponible',
            self::STATUT_PARTIELLEMENT_UTILISEE => 'Partiellement utilisée',
            self::STATUT_EPUISEE => 'Épuisée',
        ];
    }

    // ==========================================
    // RELATIONS
    // ==========================================

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

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementClient::class, 'avance_id');
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    // ==========================================
    // MÉTHODES MÉTIER
    // ==========================================

    public function getMontantRestantAttribute(): float
    {
        return max(0, (float) $this->montant - (float) $this->montant_utilise);
    }

    /**
     * Recalcule statut et montant_utilise à partir des règlements liés.
     */
    public function recalculerSolde(): void
    {
        $utilise = (float) $this->reglements()->sum('montant');
        $total = (float) $this->montant;

        $this->montant_utilise = $utilise;

        if ($utilise <= 0.01) {
            $this->statut = self::STATUT_DISPONIBLE;
        } elseif ($total - $utilise <= 0.01) {
            $this->statut = self::STATUT_EPUISEE;
        } else {
            $this->statut = self::STATUT_PARTIELLEMENT_UTILISEE;
        }

        $this->save();
    }

    public function toApiArray(): array
    {
        return [
            'id' => $this->id,
            'numero_ligne' => $this->numero_ligne,
            'client_id' => $this->client_id,
            'client' => [
                'id' => $this->client_id,
                'nom' => $this->client_nom ?: $this->client?->nom,
            ],
            'societe_emettrice' => $this->societe_emettrice,
            'numero_cheque' => $this->numero_cheque,
            'date_cheque' => $this->date_cheque?->format('Y-m-d'),
            'montant' => (float) $this->montant,
            'montant_utilise' => (float) $this->montant_utilise,
            'montant_restant' => $this->montant_restant,
            'numero_proforma' => $this->numero_proforma,
            'statut' => $this->statut,
            'statut_libelle' => self::getStatutsLabels()[$this->statut] ?? $this->statut,
            'institution' => $this->institution,
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
            ] : null,
            'bordereau_depot_path' => $this->bordereau_depot_path,
            'observations' => $this->observations,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
