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
        'reste_a_payer',
        'statut',
        'created_by',
        'created_by_name',
    ];

    protected $casts = [
        'date_facture' => 'date',
        'montant' => 'decimal:2',
        'ristourne' => 'decimal:2',
        'montant_paye' => 'decimal:2',
        'reste_a_payer' => 'decimal:2',
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
            $facture->reste_a_payer = $facture->montant - ($facture->ristourne ?? 0) - $facture->montant_paye;
        });
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

    /**
     * Vérifier s'il y a un saut de numéro
     */
    public static function verifierSaut(string $reference): ?int
    {
        $mois = date('m');
        $annee = date('y');
        $suffixe = "/{$mois}/{$annee}";

        $derniere = self::where('reference', 'LIKE', "%{$suffixe}")
            ->orderBy('reference', 'desc')
            ->value('reference');

        if (!$derniere) {
            $attendu = 1;
        } else {
            $attendu = (int) substr($derniere, 0, 4) + 1;
        }

        $saisi = (int) substr($reference, 0, 4);

        if ($saisi > $attendu) {
            return $saisi - $attendu; // nombre de numéros sautés
        }

        return null;
    }

    // ==========================================
    // MÉTHODES D'INSTANCE
    // ==========================================

    public function enregistrerPaiement(float $montant): bool
    {
        $montantPaye = (float) $this->montant_paye + $montant;
        $netAPayer = (float) $this->montant - (float) ($this->ristourne ?? 0);

        $this->montant_paye = $montantPaye;
        $this->reste_a_payer = $netAPayer - $montantPaye;

        if ($this->reste_a_payer <= 0.01) {
            $this->statut = self::STATUT_PAYEE;
            $this->reste_a_payer = 0;
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
            'reste_a_payer' => (float) $this->reste_a_payer,
            'statut' => $this->statut,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
        ];
    }
}
