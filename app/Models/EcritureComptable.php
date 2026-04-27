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
     *
     * Logique OHADA :
     *  - Débit : pour chaque ligne d'imputation saisie (charges/immo/personnel/fournisseurs) — total = HT
     *  - Débit TVA déductible (4452) : auto-généré si facture assujettie à TVA
     *  - Crédit fournisseur (401xxx) : auto-généré = HT + TVA (= TTC)
     *  - Pas d'AIB au niveau facture (uniquement au règlement si déduit)
     */
    public static function creerEcrituresFacture(FactureFournisseur $facture): void
    {
        $facture->loadMissing('imputations.compte', 'fournisseur.compteComptable', 'compte');

        $imputations = $facture->imputations;

        // Fallback legacy : pas d'imputations multiples, on utilise le compte unique
        if ($imputations->isEmpty() && $facture->compte) {
            $imputations = collect([(object) [
                'compte' => $facture->compte,
                'montant' => (float) ($facture->montant_facture ?: $facture->montant_ttc),
                'libelle' => $facture->libelle,
            ]]);
        }

        if ($imputations->isEmpty()) {
            return;
        }

        $compteCredit = $facture->fournisseur?->compteComptable
            ? $facture->fournisseur->compteComptable->numero_compte
            : '401' . str_pad($facture->fournisseur_id, 3, '0', STR_PAD_LEFT);

        $totalHt = 0.0;

        // Une ligne de débit par imputation (HT) — libellé tel que saisi par l'utilisateur
        foreach ($imputations as $imp) {
            $compte = $imp->compte;
            if (!$compte) continue;
            $montant = (float) $imp->montant;
            $totalHt += $montant;

            self::create([
                'facture_id' => $facture->id,
                'reglement_id' => null,
                'date_ecriture' => $facture->date,
                'numero_compte' => $compte->numero_compte,
                'debit' => $montant,
                'credit' => 0,
                'libelle' => $imp->libelle ?: $facture->libelle,
                'type' => self::TYPE_FACTURE,
            ]);
        }

        // Débit auto : TVA déductible si la facture est assujettie
        $montantTva = 0.0;
        if ($facture->assujetti_tva && (float) $facture->taux_tva > 0) {
            $montantTva = round($totalHt * ((float) $facture->taux_tva) / 100, 2);
            if ($montantTva > 0) {
                self::create([
                    'facture_id' => $facture->id,
                    'reglement_id' => null,
                    'date_ecriture' => $facture->date,
                    'numero_compte' => '4452',
                    'debit' => $montantTva,
                    'credit' => 0,
                    'libelle' => 'TVA déductible (' . $facture->taux_tva . '%)',
                    'type' => self::TYPE_FACTURE,
                ]);
            }
        }

        // Crédit unique : compte fournisseur = HT + TVA (TTC) — libellé = libellé facture
        $totalCredit = $totalHt + $montantTva;
        if ($totalCredit > 0) {
            self::create([
                'facture_id' => $facture->id,
                'reglement_id' => null,
                'date_ecriture' => $facture->date,
                'numero_compte' => $compteCredit,
                'debit' => 0,
                'credit' => $totalCredit,
                'libelle' => $facture->libelle,
                'type' => self::TYPE_FACTURE,
            ]);
        }
    }

    /**
     * Créer les écritures d'imputation pour un règlement
     *
     * Logique OHADA :
     *  - Débit fournisseur (401) = montant du règlement + AIB retenu (si déclaré sur ce règlement)
     *    → on solde la portion de dette correspondant à ce règlement
     *  - Crédit banque/caisse = montant effectivement payé
     *  - Crédit AIB (4473) = montant AIB retenu (si déclaré sur ce règlement)
     *  - L'écriture est équilibrée : débit total = crédit total
     */
    public static function creerEcrituresReglement(
        ReglementFournisseur $reglement,
        FactureFournisseur $facture
    ): void {
        $compteFournisseur = $facture->fournisseur?->compteComptable
            ? $facture->fournisseur->compteComptable->numero_compte
            : '401' . str_pad($facture->fournisseur_id, 3, '0', STR_PAD_LEFT);

        $montantReglement = (float) $reglement->montant;

        // Montant AIB retenu sur ce règlement (0 si pas déclaré)
        $aibDeclareSurCeReglement = $reglement->deduire_aib && (float) $facture->taux > 0;
        $montantAib = $aibDeclareSurCeReglement
            ? (float) ($reglement->montant_aib_deduit ?: $facture->montant_reduction)
            : 0.0;

        // Débit fournisseur = montant payé + AIB retenu (= total soldé sur ce règlement)
        $debitFournisseur = $montantReglement + $montantAib;

        // Libellé du mode de paiement (S/ utilisé uniquement pour chèque et virement,
        // suivant la convention comptable "Selon")
        $modeLibelle = match($reglement->mode_paiement) {
            'cheque' => 'S/Chèque N°' . ($reglement->reference ?: ''),
            'virement' => 'S/Virement Bancaire N°' . ($reglement->reference ?: ''),
            'especes' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'carte' => 'Carte bancaire',
            default => $reglement->mode_paiement,
        };

        // Débit: compte fournisseur = montant règlement + AIB retenu
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => $reglement->id,
            'date_ecriture' => $reglement->date_reglement,
            'numero_compte' => $compteFournisseur,
            'debit' => $debitFournisseur,
            'credit' => 0,
            'libelle' => $modeLibelle,
            'type' => self::TYPE_REGLEMENT,
        ]);

        // Déterminer le compte de trésorerie (utilise les codes OHADA standards
        // si aucun compte de trésorerie spécifique n'a été sélectionné)
        $compteTresorerie = $reglement->compteTresorerie
            ? $reglement->compteTresorerie->numero_compte
            : match($reglement->mode_paiement) {
                'especes' => '571',     // Caisse siège social
                default   => '521',     // Banques locales
            };

        // Libellé pour le crédit banque
        $libelleBanque = $reglement->beneficiaire ?: ($reglement->banque ?: 'Trésorerie');

        // Crédit: compte de trésorerie (banque) = montant effectivement payé
        self::create([
            'facture_id' => $facture->id,
            'reglement_id' => $reglement->id,
            'date_ecriture' => $reglement->date_reglement,
            'numero_compte' => $compteTresorerie,
            'debit' => 0,
            'credit' => $montantReglement,
            'libelle' => $libelleBanque,
            'type' => self::TYPE_REGLEMENT,
        ]);

        // Crédit: compte AIB si déclaré sur ce règlement
        if ($aibDeclareSurCeReglement && $montantAib > 0) {
            $compteAib = $reglement->compte_aib ?: ($facture->type_reduction ?: '44731');
            $compteAibModel = \App\Models\CompteComptable::where('numero_compte', $compteAib)->first();
            $libelleAib = $compteAibModel ? 'S/ ' . $compteAibModel->libelle : 'S/ AIB';

            self::create([
                'facture_id' => $facture->id,
                'reglement_id' => $reglement->id,
                'date_ecriture' => $reglement->date_reglement,
                'numero_compte' => $compteAib,
                'debit' => 0,
                'credit' => $montantAib,
                'libelle' => $libelleAib,
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
