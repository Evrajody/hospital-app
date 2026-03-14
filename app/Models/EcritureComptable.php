<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EcritureComptable extends Model
{
    protected $table = 'ecritures_comptables';

    protected $fillable = [
        'facture_id',
        'reglement_id',
        'date_ecriture',
        'numero_compte',
        'debit',
        'credit',
        'libelle',
        'type',
    ];

    protected $casts = [
        'date_ecriture' => 'date',
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    const TYPE_FACTURE = 'facture';
    const TYPE_REGLEMENT = 'reglement';

    public function facture(): BelongsTo
    {
        return $this->belongsTo(FactureFournisseur::class, 'facture_id');
    }

    public function reglement(): BelongsTo
    {
        return $this->belongsTo(ReglementFournisseur::class, 'reglement_id');
    }

    /**
     * Créer les écritures d'imputation pour une facture
     */
    public static function creerEcrituresFacture(FactureFournisseur $facture): void
    {
        $compteDebit = $facture->compte
            ? $facture->compte->numero_compte
            : null;

        $compteCredit = $facture->fournisseur?->compteComptable
            ? $facture->fournisseur->compteComptable->numero_compte
            : '401' . str_pad($facture->fournisseur_id, 3, '0', STR_PAD_LEFT);

        if (!$compteDebit) {
            return;
        }

        $montant = (float) $facture->montant_facture;

        // Débit: compte d'imputation (ex: 60111)
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => null,
            'date_ecriture' => $facture->date,
            'numero_compte' => $compteDebit,
            'debit' => $montant,
            'credit' => 0,
            'libelle' => 'S/ ' . $facture->libelle,
            'type' => self::TYPE_FACTURE,
        ]);

        // Crédit: compte fournisseur (ex: 401111.092)
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => null,
            'date_ecriture' => $facture->date,
            'numero_compte' => $compteCredit,
            'debit' => 0,
            'credit' => $montant,
            'libelle' => null,
            'type' => self::TYPE_FACTURE,
        ]);
    }

    /**
     * Créer les écritures d'imputation pour un règlement
     */
    public static function creerEcrituresReglement(
        ReglementFournisseur $reglement,
        FactureFournisseur $facture,
        bool $deduireAib = false
    ): void {
        $compteFournisseur = $facture->fournisseur?->compteComptable
            ? $facture->fournisseur->compteComptable->numero_compte
            : '401' . str_pad($facture->fournisseur_id, 3, '0', STR_PAD_LEFT);

        $montantReglement = (float) $reglement->montant;

        // Libellé du mode de paiement
        $modeLibelle = match($reglement->mode_paiement) {
            'cheque' => 'S/Chèque N°' . ($reglement->reference ?: ''),
            'virement' => 'S/Virement Bancaire N°' . ($reglement->reference ?: ''),
            'especes' => 'S/Espèces',
            'mobile_money' => 'S/Mobile Money',
            default => 'S/' . $reglement->mode_paiement,
        };

        // Débit: compte fournisseur
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => $reglement->id,
            'date_ecriture' => $reglement->date_reglement,
            'numero_compte' => $compteFournisseur,
            'debit' => $montantReglement,
            'credit' => 0,
            'libelle' => $modeLibelle,
            'type' => self::TYPE_REGLEMENT,
        ]);

        // Calcul AIB si déduction demandée
        $montantAib = 0;
        if ($deduireAib && $facture->type_reduction && $facture->taux > 0) {
            $montantAib = ($montantReglement * (float) $facture->taux) / 100;
        }

        $montantBanque = $montantReglement - $montantAib;

        // Déterminer le compte de trésorerie
        $compteTresorerie = $reglement->compteTresorerie
            ? $reglement->compteTresorerie->numero_compte
            : match($reglement->mode_paiement) {
                'especes' => '571000',
                default => '521000',
            };

        // Libellé pour le crédit banque
        $libelleBanque = $reglement->beneficiaire ?: ($reglement->banque ?: 'Trésorerie');

        // Crédit: compte de trésorerie (banque)
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => $reglement->id,
            'date_ecriture' => $reglement->date_reglement,
            'numero_compte' => $compteTresorerie,
            'debit' => 0,
            'credit' => $montantBanque,
            'libelle' => $libelleBanque,
            'type' => self::TYPE_REGLEMENT,
        ]);

        // Crédit: compte AIB (si déduction)
        if ($montantAib > 0) {
            $compteAib = $facture->type_reduction ?: '44731';

            self::create([
                'facture_id' => $facture->id,
                'reglement_id' => $reglement->id,
                'date_ecriture' => $reglement->date_reglement,
                'numero_compte' => $compteAib,
                'debit' => 0,
                'credit' => $montantAib,
                'libelle' => 'S/ Acompte',
                'type' => self::TYPE_REGLEMENT,
            ]);
        }
    }

    /**
     * Supprimer les écritures d'un règlement
     */
    public static function supprimerEcrituresReglement(int $reglementId): void
    {
        self::where('reglement_id', $reglementId)->delete();
    }
}
