<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FactureClient extends Model
{
    use SoftDeletes;

    protected $table = 'factures_clients';

    protected $fillable = [
        'reference',
        'date_facture',
        'montant',
        'ristourne',
        'client_id',
        'client_nom',
        'montant_paye',
        'total_rejet',
        'total_perte',
        'reste_a_payer',
        'date_solde',
        'statut',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'montant' => 'decimal:2',
        'ristourne' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'total_rejet' => 'decimal:2',
        'total_perte' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
        'date_solde' => 'date',
    ];

    const STATUT_NON_PAYEE = 'non_payee';
    const STATUT_PARTIELLEMENT_PAYEE = 'partiellement_payee';
    const STATUT_PAYEE = 'payee';

    // ==========================================
    // RELATIONS
    // ==========================================

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function createur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function reglements(): HasMany
    {
        return $this->hasMany(ReglementClient::class, 'facture_id');
    }

    // ==========================================
    // BOOT
    // ==========================================

    protected static function boot()
    {
        parent::boot();

        static::saving(function ($facture) {
            // Règle de calcul unique : le reste à payer tient compte du montant payé,
            // mais aussi des rejets (chèques rejetés, inclus dans le montant du règlement)
            // et des pertes. reste = montant - ristourne - (payé + rejet + perte).
            $reste = (float) $facture->montant
                - (float) ($facture->ristourne ?? 0)
                - (float) $facture->montant_paye
                - (float) ($facture->total_rejet ?? 0)
                - (float) ($facture->total_perte ?? 0);
            $facture->reste_a_payer = max(0, $reste);
        });
    }

    // ==========================================
    // CALCUL DES SOLDES
    // ==========================================

    /**
     * Recalcule montant_payé, total_rejet, total_perte, reste_a_payer et statut
     * à partir des règlements liés à la facture. Source de vérité unique appelée
     * après toute création / modification / suppression de règlement.
     *
     * - montant_payé = somme des règlements NON "perte" (encaissements)
     * - total_rejet  = somme des montant_rejet (le rejet fait partie du règlement)
     * - total_perte  = somme des règlements de type "perte"
     */
    public function recalculerSoldes(): bool
    {
        $reglements = $this->relationLoaded('reglements') ? $this->reglements : $this->reglements()->get();

        $paye = (float) $reglements->where('type_reglement', '!=', 'perte')->sum('montant');
        $rejet = (float) $reglements->sum('montant_rejet');
        $perte = (float) $reglements->where('type_reglement', 'perte')->sum('montant');

        $this->montant_paye = $paye;
        $this->total_rejet = $rejet;
        $this->total_perte = $perte;

        $net = (float) $this->montant - (float) ($this->ristourne ?? 0);
        $reste = max(0, $net - ($paye + $rejet + $perte));

        if ($reste <= 0.01) {
            $this->statut = self::STATUT_PAYEE;
            $this->date_solde = now();
        } elseif ($paye + $rejet + $perte > 0.01) {
            $this->statut = self::STATUT_PARTIELLEMENT_PAYEE;
            $this->date_solde = null;
        } else {
            $this->statut = self::STATUT_NON_PAYEE;
            $this->date_solde = null;
        }

        // reste_a_payer est (re)calculé par le hook saving() à partir des colonnes ci-dessus.
        return $this->save();
    }

    // ==========================================
    // MÉTHODES STATIQUES
    // ==========================================

    /**
     * Générer la prochaine référence au format xxxx/mm/yy
     */
    public static function genererReference(): string
    {
        $mois = date('m');
        $annee = date('y');
        $suffixe = "/{$mois}/{$annee}";

        $derniere = self::where('reference', 'LIKE', "%{$suffixe}")
            ->orderBy('reference', 'desc')
            ->value('reference');

        if ($derniere) {
            $numero = (int) substr($derniere, 0, 4) + 1;
        } else {
            $numero = 1;
        }

        return str_pad($numero, 4, '0', STR_PAD_LEFT) . $suffixe;
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    public function enregistrerPaiement(float $montant): bool
    {
        $montantPaye = (float) $this->montant_paye + $montant;
        $netAPayer = (float) $this->montant - (float) ($this->ristourne ?? 0);

        $this->montant_paye = $montantPaye;
        // Le hook saving() recalcule reste_a_payer (payé + rejet + perte) ; on évalue
        // ici le statut avec la même règle pour rester cohérent.
        $reste = max(0, $netAPayer - $montantPaye - (float) ($this->total_rejet ?? 0) - (float) ($this->total_perte ?? 0));

        if ($reste <= 0.01) {
            $this->statut = self::STATUT_PAYEE;
            $this->date_solde = now();
        } else {
            $this->statut = self::STATUT_PARTIELLEMENT_PAYEE;
        }

        return $this->save();
    }

    public function toApiArray(): array
    {
        $ristourne = (float) ($this->ristourne ?? 0);
        $montant = (float) $this->montant;

        return [
            'id' => $this->id,
            'reference' => $this->reference,
            'date_facture' => $this->date_facture?->format('Y-m-d'),
            'montant' => $montant,
            'ristourne' => $ristourne,
            'net_a_payer' => $montant - $ristourne,
            'client_id' => $this->client_id,
            'client' => [
                'id' => $this->client_id,
                'nom' => $this->client_nom ?: $this->client?->nom,
                'telephone' => $this->client?->telephone,
            ],
            'montant_paye' => (float) $this->montant_paye,
            'total_rejet' => (float) ($this->total_rejet ?? 0),
            'total_perte' => (float) ($this->total_perte ?? 0),
            'reste_a_payer' => (float) $this->reste_a_payer,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
