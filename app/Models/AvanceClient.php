<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
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
        'societe_emettrice_client_id',
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

    /**
     * Client (type société) qui émet le chèque d'avance.
     */
    public function societeEmettrice(): BelongsTo
    {
        return $this->belongsTo(Client::class, 'societe_emettrice_client_id');
    }

    /**
     * Clients bénéficiaires de l'avance (relation many-to-many).
     */
    public function beneficiaires(): BelongsToMany
    {
        return $this->belongsToMany(Client::class, 'avance_client_beneficiaires', 'avance_id', 'client_id')
            ->withPivot('client_nom')
            ->withTimestamps();
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
        $emetteur = $this->societeEmettrice;

        return [
            'id' => $this->id,
            'numero_ligne' => $this->numero_ligne,
            // Société émettrice EN PREMIER (client type société, porteur du n° de compte).
            'societe_emettrice_client_id' => $this->societe_emettrice_client_id,
            'societe_emettrice_client' => $emetteur ? [
                'id' => $emetteur->id,
                'nom' => $emetteur->nom,
                'numero_compte' => $emetteur->compteComptable?->numero_compte,
            ] : null,
            'societe_emettrice' => $this->societe_emettrice,
            // Bénéficiaires (many-to-many).
            'beneficiaires' => $this->beneficiaires->map(fn ($b) => [
                'id' => $b->id,
                'nom' => $b->pivot->client_nom ?: $b->nom,
                'numero_compte' => $b->compteComptable?->numero_compte,
            ])->values()->all(),
            'client_id' => $this->client_id,
            'client' => [
                'id' => $this->client_id,
                'nom' => $this->client_nom ?: $this->client?->nom,
            ],
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
