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
     * Créer les écritures d'imputation pour une facture (modèle 🅰️ — AIB au règlement)
     *
     * Logique OHADA :
     *  - Bloc Débits : charges/immo/personnel (codes 2/42/6) — total = HT
     *  - TVA déductible (445) : auto-générée au débit si facture assujettie
     *  - Bloc Crédits : fournisseurs (codes 401/481) — total = TTC (dette envers fournisseur)
     *  - L'AIB sera comptabilisée au règlement (si l'utilisateur coche "Déclarer l'AIB")
     *
     *  Equilibre : Débits (HT + TVA) = Crédits Fournisseurs = TTC
     */
    public static function creerEcrituresFacture(FactureFournisseur $facture): void
    {
        $facture->loadMissing('imputations.compte', 'fournisseur.compteComptable', 'compte');

        $imputations = $facture->imputations;

        // Fallback legacy : pas d'imputations multiples, on utilise le compte unique
        if ($imputations->isEmpty() && $facture->compte) {
            $imputations = collect([(object) [
                'compte' => $facture->compte,
                'nature' => 'debit',
                'montant' => (float) ($facture->montant_facture ?: $facture->montant_ttc),
                'libelle' => $facture->libelle,
            ]]);
        }

        if ($imputations->isEmpty()) {
            return;
        }

        // Séparer les imputations selon leur nature
        $debits = $imputations->filter(fn($i) => ($i->nature ?? 'debit') === 'debit');
        $credits = $imputations->filter(fn($i) => ($i->nature ?? 'debit') === 'credit');

        $totalHt = 0.0;

        // === Lignes DÉBIT saisies (charges/immo/personnel) — total = HT ===
        foreach ($debits as $imp) {
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

        // === Débit auto : TVA déductible si assujettie ===
        if ($facture->assujetti_tva && (float) $facture->taux_tva > 0) {
            $montantTva = round($totalHt * ((float) $facture->taux_tva) / 100, 2);
            if ($montantTva > 0) {
                self::create([
                    'facture_id' => $facture->id,
                    'reglement_id' => null,
                    'date_ecriture' => $facture->date,
                    'numero_compte' => '445',
                    'debit' => $montantTva,
                    'credit' => 0,
                    'libelle' => 'TVA déductible (' . $facture->taux_tva . '%)',
                    'type' => self::TYPE_FACTURE,
                ]);
            }
        }

        // === Lignes CRÉDIT saisies par l'utilisateur (fournisseurs 401/481) — total = TTC ===
        // L'AIB n'est PAS au niveau de la facture ; elle est gérée au règlement.
        // Plus de fallback : seules les lignes saisies dans l'imputation comptable apparaissent.
        foreach ($credits as $imp) {
            $compte = $imp->compte;
            if (!$compte) continue;
            self::create([
                'facture_id' => $facture->id,
                'reglement_id' => null,
                'date_ecriture' => $facture->date,
                'numero_compte' => $compte->numero_compte,
                'debit' => 0,
                'credit' => (float) $imp->montant,
                'libelle' => $imp->libelle ?: $facture->libelle,
                'type' => self::TYPE_FACTURE,
            ]);
        }
    }

    /**
     * Créer les écritures d'imputation pour un règlement (multi-fournisseur)
     *
     * Logique OHADA — Approche A :
     *  - Lignes du règlement (multi-comptes 401/481) : chacune devient un débit fournisseur
     *  - Total des lignes = montant total soldé sur les comptes fournisseurs
     *  - Crédit banque/caisse = montant règlement (= total soldé - AIB retenu)
     *  - Crédit AIB (44xx) = AIB retenu (si déclaré sur ce règlement)
     *  - Equilibre : Σ débits fournisseurs = montant + AIB ; Σ crédits = montant + AIB ✅
     *
     *  Si pas de lignes (legacy), fallback sur compte_credit_id ou compte fournisseur générique.
     */
    public static function creerEcrituresReglement(
        ReglementFournisseur $reglement,
        FactureFournisseur $facture
    ): void {
        $reglement->loadMissing('compteCredit', 'lignes.compte');
        $montantReglement = (float) $reglement->montant;

        // Montant AIB retenu sur ce règlement
        $aibDeclareSurCeReglement = $reglement->deduire_aib && (float) $facture->taux > 0;
        $montantAib = $aibDeclareSurCeReglement
            ? (float) ($reglement->montant_aib_deduit ?: $facture->montant_reduction)
            : 0.0;

        // Libellé du mode de paiement
        $modeLibelle = match($reglement->mode_paiement) {
            'cheque' => 'S/Chèque N°' . ($reglement->reference ?: ''),
            'virement' => 'S/Virement Bancaire N°' . ($reglement->reference ?: ''),
            'especes' => 'Espèces',
            'mobile_money' => 'Mobile Money',
            'carte' => 'Carte bancaire',
            default => $reglement->mode_paiement,
        };

        // === DÉBITS FOURNISSEURS — multi-comptes via lignes ===
        $lignes = $reglement->lignes;
        if ($lignes->isNotEmpty()) {
            // Multi-fournisseur : une ligne de débit par compte saisi
            foreach ($lignes as $ligne) {
                $compte = $ligne->compte;
                if (!$compte) continue;
                self::create([
                    'facture_id' => $facture->id,
                    'reglement_id' => $reglement->id,
                    'date_ecriture' => $reglement->date_reglement,
                    'numero_compte' => $compte->numero_compte,
                    'debit' => (float) $ligne->montant,
                    'credit' => 0,
                    'libelle' => $ligne->libelle ?: $modeLibelle,
                    'type' => self::TYPE_REGLEMENT,
                ]);
            }
        } else {
            // Fallback legacy : compte_credit_id ou compte du fournisseur
            $compteFournisseur = $reglement->compteCredit
                ? $reglement->compteCredit->numero_compte
                : ($facture->fournisseur?->compteComptable?->numero_compte
                    ?? '401' . str_pad($facture->fournisseur_id, 3, '0', STR_PAD_LEFT));
            $debitFournisseur = $montantReglement + $montantAib;

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
        }

        // === CRÉDIT BANQUE/CAISSE = montant règlement (cash réellement payé) ===
        $compteTresorerie = $reglement->compteTresorerie
            ? $reglement->compteTresorerie->numero_compte
            : match($reglement->mode_paiement) {
                'especes' => '571',     // Caisse siège social
                default   => '521',     // Banques locales
            };
        $libelleBanque = $reglement->beneficiaire ?: ($reglement->banque ?: 'Trésorerie');

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

        // === CRÉDIT AIB si déclaré ===
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
