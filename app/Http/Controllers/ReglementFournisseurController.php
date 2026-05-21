<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\ReglementFournisseur;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\CompteComptable;
use App\Models\CompteBancaire;
use App\Models\Banque;
use App\Models\EcritureComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Barryvdh\DomPDF\Facade\Pdf;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReglementFournisseurController extends Controller
{
    /**
     * Afficher la liste des règlements (Vue Inertia)
     */
    public function indexView(Request $request): InertiaResponse
    {
        $query = ReglementFournisseur::with(['facture', 'fournisseur', 'createur']);

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par mode de paiement
        if ($request->filled('mode_paiement')) {
            $query->where('mode_paiement', $request->mode_paiement);
        }

        // Filtre par période
        if ($request->filled('date_debut') && $request->filled('date_fin')) {
            $query->periode($request->date_debut, $request->date_fin);
        }

        // Recherche
        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        // Tri
        $sortField = $request->input('sort', 'date_reglement');
        $sortOrder = $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc';

        $relationSortMap = [
            'facture' => ['factures_fournisseurs', 'facture_id', 'numero_piece'],
            'fournisseur' => ['fournisseurs', 'fournisseur_id', 'nom'],
            'compte_bancaire' => ['comptes_bancaires', 'compte_bancaire_id', 'numero_compte'],
        ];

        $allowedDirectSorts = ['date_reglement', 'montant', 'mode_paiement', 'reference', 'beneficiaire', 'created_at'];

        if (isset($relationSortMap[$sortField])) {
            [$table, $fk, $column] = $relationSortMap[$sortField];
            $query->leftJoin($table, "reglements_fournisseurs.{$fk}", '=', "{$table}.id")
                  ->orderBy("{$table}.{$column}", $sortOrder)
                  ->select('reglements_fournisseurs.*');
        } elseif (in_array($sortField, $allowedDirectSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('date_reglement', $sortOrder);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $reglementsPaginated = $query->paginate($perPage);

        // Formater les règlements
        $reglements = $reglementsPaginated->map(fn($r) => $r->toApiArray());

        // Fournisseurs pour les filtres
        $fournisseurs = Fournisseur::select('id', 'nom')    
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => ['id' => $f->id, 'nom' => $f->nom]);

        // Statistiques
        $stats = ReglementFournisseur::getStatistiques();

        // Factures non soldées (validées ou partiellement payées) seulement
        $facturesImpayees = FactureFournisseur::with('fournisseur')
            ->nonPayee()
            ->orderBy('date', 'desc')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'numero' => $f->numero_piece ?? $f->numero,
                'libelle' => $f->libelle,
                'date' => $f->date,
                'fournisseur' => $f->fournisseur ? ['id' => $f->fournisseur->id, 'nom' => $f->fournisseur->nom] : null,
                'fournisseur_nom' => $f->fournisseur?->nom,
                'montant_ttc' => $f->montant_ttc,
                'montant_paye' => $f->montant_paye,
                'reste_a_payer' => $f->reste_a_payer ?? ($f->montant_ttc - $f->montant_paye),
                'statut' => $f->statut,
            ]);

        return Inertia::render('ReglementsFournisseurs/Index', [
            'reglements' => $reglements,
            'fournisseurs' => $fournisseurs,
            'facturesImpayees' => $facturesImpayees,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $reglementsPaginated->currentPage(),
                'per_page' => $reglementsPaginated->perPage(),
                'total' => $reglementsPaginated->total(),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Lister les règlements (API)
     */
    public function index(Request $request): JsonResponse
    {
        $query = ReglementFournisseur::with(['facture', 'fournisseur']);

        // Filtre par facture
        if ($request->filled('facture_id')) {
            $query->where('facture_id', $request->facture_id);
        }

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }

        // Tri
        $sortField = $request->input('sort', 'date_reglement');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $reglements = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $reglements->map(fn($r) => $r->toApiArray()),
            'pagination' => [
                'current_page' => $reglements->currentPage(),
                'per_page' => $reglements->perPage(),
                'total' => $reglements->total(),
                'last_page' => $reglements->lastPage(),
            ],
        ]);
    }

    /**
     * Afficher un règlement (API)
     */
    public function show(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture', 'fournisseur', 'compteTresorerie', 'compteCredit', 'lignes.compte', 'createur'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $reglement->toApiArray(),
        ]);
    }

    /**
     * Créer un règlement (API)
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->validationRules());

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Vérifier que la facture peut être payée
        $facture = FactureFournisseur::findOrFail($request->facture_id);

        if (!$facture->peut_etre_payee && $facture->statut !== FactureFournisseur::STATUT_BROUILLON) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture ne peut pas recevoir de règlement',
            ], 422);
        }

        // Vérifier que le montant ne dépasse pas le reste à payer
        // Convertir en float pour une comparaison correcte
        $montantReglement = (float) $request->montant;
        $resteAPayer = (float) $facture->reste_a_payer;

        // Tolérance de 0.01 pour éviter les erreurs d'arrondi
        if ($montantReglement > ($resteAPayer + 0.01)) {
            return response()->json([
                'success' => false,
                'message' => "Le montant du règlement (" . number_format($montantReglement, 0, ',', ' ') . " XOF) dépasse le reste à payer (" . number_format($resteAPayer, 0, ',', ' ') . " XOF)",
                'errors' => ['montant' => ["Le montant ne peut pas dépasser " . number_format($resteAPayer, 0, ',', ' ') . " XOF"]],
            ], 422);
        }

        // Vérifier le solde du compte bancaire si mode chèque ou virement
        $compteBancaire = null;
        if (in_array($request->mode_paiement, ['cheque', 'virement']) && $request->compte_bancaire_id) {
            $compteBancaire = CompteBancaire::with('banque')->find($request->compte_bancaire_id);

            if ($compteBancaire && !$compteBancaire->soldeEstSuffisant($montantReglement) && !$request->force_insufficient_balance) {
                return response()->json([
                    'success' => false,
                    'insufficient_balance' => true,
                    'message' => 'Solde insuffisant sur le compte bancaire',
                    'solde_actuel' => (float) $compteBancaire->solde,
                    'montant_demande' => $montantReglement,
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // Générer le numéro de règlement
            $numeroReglement = $request->numero_reglement ?: ReglementFournisseur::genererNumeroReglement();

            // Déterminer banque et numéro de compte
            $banqueNom = $request->banque;
            $numeroCompteBancaire = $request->numero_compte_bancaire;

            if ($compteBancaire) {
                $banqueNom = $compteBancaire->banque->nom;
                $numeroCompteBancaire = $compteBancaire->numero_compte;
            }

            // AIB : on enregistre juste la déclaration (date du jour)
            $deduireAib = (bool) $request->input('deduire_aib', false);
            $compteAib = null;
            $dateAib = null;

            if ($deduireAib && $facture->type_reduction && $facture->taux > 0) {
                $compteAib = $facture->type_reduction;
                $dateAib = $request->date_aib ?? now()->toDateString();
            }

            // Snapshot établissement pour le bordereau de règlement
            $etablissement = \App\Models\Setting::getEtablissement();

            // Créer le règlement (utiliser le montant converti en float)
            $reglement = ReglementFournisseur::create([
                'numero_reglement' => $numeroReglement,
                'date_reglement' => $request->date_reglement,
                'facture_id' => $request->facture_id,
                'fournisseur_id' => $facture->fournisseur_id,
                'fournisseur_nom' => $facture->fournisseur_nom ?: $facture->fournisseur?->nom,
                'facture_numero' => $facture->numero_piece,
                'montant' => $montantReglement,
                'mode_paiement' => $request->mode_paiement,
                'reference' => $request->reference,
                'beneficiaire' => $request->beneficiaire,
                'banque' => $banqueNom,
                'numero_compte_bancaire' => $numeroCompteBancaire,
                'compte_tresorerie_id' => $request->compte_tresorerie_id,
                'compte_credit_id' => $request->compte_credit_id,
                'observations' => $request->observations,
                'deduire_aib' => $deduireAib,
                'montant_aib_deduit' => ($deduireAib && $facture->taux > 0)
                    ? (float) $facture->montant_reduction
                    : 0,
                'compte_aib' => $compteAib,
                'date_aib' => $dateAib,
                'statut' => ReglementFournisseur::STATUT_VALIDE,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name,
                'etablissement_nom' => $etablissement['nom'],
                'etablissement_directeur' => $etablissement['directeur'],
            ]);

            // Synchroniser les lignes (multi-fournisseur)
            $this->syncReglementLignes($reglement, $request->input('lignes', []));

            // Débiter le compte bancaire si applicable
            if ($compteBancaire) {
                $compteBancaire->debiter($montantReglement);
            }

            // Mettre à jour la facture
            $facture->enregistrerPaiement($montantReglement);

            // Pas de génération auto des écritures du règlement à la création.
            // L'utilisateur déclenchera explicitement via le bouton "Générer les écritures comptables".

            DB::commit();

            ActivityLog::log('create', 'reglement_fournisseur', "Règlement {$reglement->numero_reglement} de {$montantReglement} XOF sur facture {$facture->numero_piece}", $reglement, ['numero_reglement' => $reglement->numero_reglement, 'montant' => $montantReglement, 'facture_numero' => $facture->numero_piece]);

            $reglement->load(['facture', 'fournisseur']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement enregistré avec succès',
                'data' => $reglement->toApiArray(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un règlement (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture', 'compteTresorerie'])->findOrFail($id);

        if (!$reglement->est_modifiable) {
            return response()->json([
                'success' => false,
                'message' => 'Ce règlement ne peut plus être modifié',
            ], 422);
        }

        $validator = Validator::make($request->all(), $this->validationRules($id));

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $facture = $reglement->facture;
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => 'Facture liée introuvable',
            ], 422);
        }

        // ==========================================
        // SNAPSHOT DE L'ANCIEN ÉTAT (pour pouvoir l'annuler)
        // ==========================================
        $ancienMontant = (float) $reglement->montant;
        $ancienMode = $reglement->mode_paiement;
        $ancienCompteId = $reglement->compte_tresorerie_id;

        // ==========================================
        // VALIDATION DU NOUVEAU SOLDE BANCAIRE
        // ==========================================
        $nouveauMontant = (float) ($request->montant ?? $ancienMontant);
        $nouveauMode = $request->mode_paiement ?? $ancienMode;
        $nouveauCompteId = $request->compte_bancaire_id ?? $request->compte_tresorerie_id ?? $ancienCompteId;

        $nouveauCompte = null;
        if (in_array($nouveauMode, ['cheque', 'virement']) && $nouveauCompteId) {
            $nouveauCompte = CompteBancaire::with('banque')->find($nouveauCompteId);

            if ($nouveauCompte) {
                // Calculer le solde "virtuel" en remettant l'ancien débit (si même compte)
                $soldeVirtuel = (float) $nouveauCompte->solde;
                if ($nouveauCompte->id === $ancienCompteId && in_array($ancienMode, ['cheque', 'virement'])) {
                    $soldeVirtuel += $ancienMontant;
                }

                if ($soldeVirtuel < $nouveauMontant && !$request->force_insufficient_balance) {
                    return response()->json([
                        'success' => false,
                        'insufficient_balance' => true,
                        'message' => 'Solde insuffisant sur le compte bancaire',
                        'solde_actuel' => $soldeVirtuel,
                        'montant_demande' => $nouveauMontant,
                    ], 422);
                }
            }
        }

        // ==========================================
        // VÉRIFIER QUE LE NOUVEAU MONTANT NE DÉPASSE PAS LE RESTE
        // ==========================================
        $resteSansCeReglement = (float) $facture->montant_net - ((float) $facture->montant_paye - $ancienMontant);
        if ($nouveauMontant > $resteSansCeReglement + 0.01) {
            return response()->json([
                'success' => false,
                'message' => 'Le nouveau montant dépasse le reste à payer',
                'errors' => ['montant' => ['Le montant ne peut pas dépasser ' . number_format($resteSansCeReglement, 0, ',', ' ') . ' XOF']],
            ], 422);
        }

        try {
            DB::beginTransaction();

            // ==========================================
            // 1. ANNULER L'ANCIEN ÉTAT
            // ==========================================

            // a) Reverser l'ancien débit du compte bancaire (si applicable)
            if (in_array($ancienMode, ['cheque', 'virement']) && $ancienCompteId) {
                CompteBancaire::where('id', $ancienCompteId)->increment('solde', $ancienMontant);
            }

            // b) Reverser le paiement sur la facture (et remettre le statut cohérent
            //    sinon enregistrerPaiement refusera d'agir sur une facture déjà PAYEE)
            $facture->montant_paye = max(0, (float) $facture->montant_paye - $ancienMontant);
            $facture->reste_a_payer = (float) $facture->montant_net - $facture->montant_paye;
            if ($facture->montant_paye <= 0.01) {
                $facture->montant_paye = 0;
                $facture->reste_a_payer = (float) $facture->montant_net;
                $facture->statut = FactureFournisseur::STATUT_VALIDEE;
            } else {
                $facture->statut = FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE;
            }
            $facture->save();

            // c) Supprimer les anciennes écritures comptables du règlement
            EcritureComptable::supprimerEcrituresReglement($reglement->id);

            // ==========================================
            // 2. CALCULER LES VALEURS AIB
            // ==========================================
            $deduireAib = (bool) $request->input('deduire_aib', $reglement->deduire_aib);
            $compteAib = null;
            $dateAib = null;
            $montantAibDeduit = 0.0;

            if ($deduireAib && $facture->type_reduction && (float) $facture->taux > 0) {
                $compteAib = $facture->type_reduction;
                $dateAib = $request->date_aib ?? $reglement->date_aib ?? now()->toDateString();
                $montantAibDeduit = (float) $facture->montant_reduction;
            }

            // ==========================================
            // 3. DÉTERMINER NOM BANQUE / NUMÉRO COMPTE
            // ==========================================
            $banqueNom = $request->banque ?? $reglement->banque;
            $numeroCompteBancaire = $request->numero_compte_bancaire ?? $reglement->numero_compte_bancaire;

            if ($nouveauCompte) {
                $banqueNom = $nouveauCompte->banque->nom;
                $numeroCompteBancaire = $nouveauCompte->numero_compte;
            } elseif ($nouveauMode === 'especes') {
                $banqueNom = null;
                $numeroCompteBancaire = null;
                $nouveauCompteId = null;
            }

            // ==========================================
            // 4. APPLIQUER LES NOUVELLES VALEURS
            // ==========================================
            $reglement->update([
                'date_reglement' => $request->date_reglement ?? $reglement->date_reglement,
                'montant' => $nouveauMontant,
                'mode_paiement' => $nouveauMode,
                'reference' => $request->reference,
                'beneficiaire' => $request->beneficiaire,
                'banque' => $banqueNom,
                'numero_compte_bancaire' => $numeroCompteBancaire,
                'compte_tresorerie_id' => $nouveauCompteId,
                'compte_credit_id' => $request->compte_credit_id ?? $reglement->compte_credit_id,
                'observations' => $request->observations,
                'deduire_aib' => $deduireAib,
                'montant_aib_deduit' => $montantAibDeduit,
                'compte_aib' => $compteAib,
                'date_aib' => $dateAib,
            ]);

            // ==========================================
            // 5. APPLIQUER LE NOUVEAU SUR LA FACTURE
            // ==========================================
            $facture->refresh();
            $facture->enregistrerPaiement($nouveauMontant);

            // ==========================================
            // 6. DÉBITER LE NOUVEAU COMPTE BANCAIRE
            // ==========================================
            if ($nouveauCompte) {
                $nouveauCompte->debiter($nouveauMontant);
            }

            // ==========================================
            // 7. SYNCHRONISER LES LIGNES (multi-fournisseur)
            // ==========================================
            if ($request->has('lignes')) {
                $this->syncReglementLignes($reglement, $request->input('lignes', []));
            }

            // ==========================================
            // 8. RE-SYNC DES ÉCRITURES UNIQUEMENT SI ELLES EXISTAIENT DÉJÀ
            // ==========================================
            $avaitEcritures = EcritureComptable::where('reglement_id', $reglement->id)->exists();
            if ($avaitEcritures && $nouveauMode !== 'especes') {
                EcritureComptable::supprimerEcrituresReglement($reglement->id);
                $reglement->load(['compteTresorerie', 'lignes.compte']);
                $facture->load(['fournisseur.compteComptable']);
                EcritureComptable::creerEcrituresReglement($reglement, $facture);
            }

            DB::commit();

            ActivityLog::log('update', 'reglement_fournisseur', "Modification du règlement {$reglement->numero_reglement}", $reglement, ['numero_reglement' => $reglement->numero_reglement]);

            $reglement->load(['facture', 'fournisseur']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement modifié avec succès',
                'data' => $reglement->toApiArray(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer (annuler) un règlement (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::findOrFail($id);
        $facture = $reglement->facture;

        try {
            DB::beginTransaction();

            $montant = (float) $reglement->montant;

            // Supprimer les écritures comptables liées
            EcritureComptable::supprimerEcrituresReglement($reglement->id);

            // Supprimer le règlement
            $reglement->delete();

            // Reverser le paiement sur la facture
            if ($facture) {
                $facture->montant_paye = max(0, (float) $facture->montant_paye - $montant);
                $facture->reste_a_payer = (float) $facture->montant_net - (float) $facture->montant_paye;

                if ($facture->montant_paye <= 0.01) {
                    $facture->statut = FactureFournisseur::STATUT_VALIDEE;
                    $facture->montant_paye = 0;
                } elseif ($facture->reste_a_payer <= 0.01) {
                    $facture->statut = FactureFournisseur::STATUT_PAYEE;
                    $facture->reste_a_payer = 0;
                } else {
                    $facture->statut = FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE;
                }

                $facture->save();
            }

            DB::commit();

            ActivityLog::log('delete', 'reglement_fournisseur', "Suppression du règlement {$reglement->numero_reglement}", null, ['numero_reglement' => $reglement->numero_reglement, 'montant' => $montant]);

            return response()->json([
                'success' => true,
                'message' => 'Règlement supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du règlement',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer un numéro de règlement (API)
     */
    public function genererNumero(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'numero_reglement' => ReglementFournisseur::genererNumeroReglement(),
        ]);
    }

    /**
     * Statistiques des règlements (API)
     */
    public function stats(Request $request): JsonResponse
    {
        $fournisseurId = $request->filled('fournisseur_id') ? (int) $request->fournisseur_id : null;
        $stats = ReglementFournisseur::getStatistiques($fournisseurId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Générer (ou re-générer) les écritures comptables d'un règlement.
     * Refuse si le mode = espèces, ou si la facture n'a pas d'imputations comptables.
     */
    public function creerEcritures(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture.fournisseur.compteComptable', 'facture.imputations.compte', 'compteTresorerie', 'lignes.compte'])
            ->findOrFail($id);

        if ($reglement->mode_paiement === 'especes') {
            return response()->json([
                'success' => false,
                'message' => 'Pas d\'écritures comptables pour un règlement en espèces.',
            ], 422);
        }

        $facture = $reglement->facture;
        if (!$facture) {
            return response()->json([
                'success' => false,
                'message' => 'Facture liée introuvable.',
            ], 422);
        }

        // On ne peut pas générer les écritures du règlement tant que la facture n'a pas d'imputations comptables.
        if ($facture->imputations->isEmpty() && !$facture->compte_id) {
            return response()->json([
                'success' => false,
                'reason' => 'missing',
                'message' => "Impossible de générer les écritures de ce règlement tant que la facture n'a pas d'imputation comptable. Saisissez d'abord l'imputation sur la facture.",
            ], 422);
        }

        try {
            EcritureComptable::supprimerEcrituresReglement($reglement->id);
            EcritureComptable::creerEcrituresReglement($reglement, $facture);

            return response()->json([
                'success' => true,
                'message' => 'Écritures comptables générées avec succès.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la génération : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les règlements d'une facture (API)
     */
    public function parFacture(int $factureId): JsonResponse
    {
        $reglements = ReglementFournisseur::where('facture_id', $factureId)
            ->with(['createur'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        return response()->json([
            'success' => true,
            'data' => $reglements,
        ]);
    }

    /**
     * Générer le PDF du Bordereau de Règlement
     */
    public function mandat(int $id)
    {
        $reglement = ReglementFournisseur::with(['facture.fournisseur', 'fournisseur'])
            ->findOrFail($id);

        $data = $this->buildPdfData($reglement);
        $data['facture'] = $reglement->facture;
        $data['user'] = auth()->user();
        $data['resteAPayerLettres'] = strtoupper($this->convertirMontantEnLettres((float) $reglement->facture->reste_a_payer) . ' FRANCS');

        $pdf = Pdf::loadView('pdf.mandat-paiement', $data);
        $pdf->setPaper('a4', 'portrait');

        return $pdf->stream("bordereau-reglement-{$reglement->id}.pdf");
    }

    /**
     * Données du bordereau de règlement (JSON pour drawer)
     */
    public function mandatData(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture', 'fournisseur'])
            ->findOrFail($id);

        $facture = $reglement->facture;
        $montant = (float) $reglement->montant;
        $montantFacture = (float) $facture->montant_facture;
        $montantAvoir = (float) ($facture->avoir ?? 0);
        $montantAib = (float) ($reglement->montant_aib_deduit ?? 0);
        $tauxAib = $facture->taux ? (float) $facture->taux : 0;
        $resteAPayer = (float) $facture->reste_a_payer;

        $modeLabel = match($reglement->mode_paiement) {
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement bancaire',
            'carte' => 'Carte bancaire',
            'mobile_money' => 'Mobile Money',
            default => $reglement->mode_paiement,
        };

        return response()->json([
            'success' => true,
            'etablissement' => [
                'nom' => $reglement->etablissement_nom ?: \App\Models\Setting::getEtablissement()['nom'],
                'directeur' => $reglement->etablissement_directeur ?: \App\Models\Setting::getEtablissement()['directeur'],
                'pays' => \App\Models\Setting::getEtablissement()['pays'],
                'adresse' => \App\Models\Setting::getEtablissement()['adresse'],
                'telephone' => \App\Models\Setting::getEtablissement()['telephone'],
            ],
            'reglement' => [
                'id' => $reglement->id,
                'numero_reglement' => $reglement->numero_reglement,
                'date_reglement' => $reglement->date_reglement?->format('d/m/Y'),
                'montant' => $montant,
                'montant_formate' => number_format($montant, 0, ',', ' '),
                'montant_en_lettres' => strtoupper($this->convertirMontantEnLettres($montant) . ' FRANCS'),
                'mode_paiement' => $modeLabel,
                'reference' => $reglement->reference,
                'beneficiaire' => $reglement->beneficiaire,
                'banque' => $reglement->banque,
                'numero_compte_bancaire' => $reglement->numero_compte_bancaire,
            ],
            'facture' => [
                'numero_piece' => $reglement->facture_numero ?: $facture->numero_piece,
                'libelle' => $facture->libelle,
                'reference_facture' => $facture->reference_facture,
                'montant_facture' => number_format($montantFacture, 0, ',', ' '),
                'taux_tva' => $facture->taux_tva ? (float) $facture->taux_tva : 0,
                'montant_tva' => number_format((float) ($facture->montant_tva ?? 0), 0, ',', ' '),
                'montant_ttc' => number_format((float) ($facture->montant_ttc ?? 0), 0, ',', ' '),
                'montant_avoir' => number_format($montantAvoir, 0, ',', ' '),
                'taux_aib' => $tauxAib,
                'montant_aib' => number_format($montantAib, 0, ',', ' '),
                'montant_paye' => number_format((float) $facture->montant_paye, 0, ',', ' '),
                'montant_paye_lettres' => strtoupper($this->convertirMontantEnLettres((float) $facture->montant_paye) . ' FRANCS'),
                'reste_a_payer' => number_format($resteAPayer, 0, ',', ' '),
                'reste_a_payer_lettres' => strtoupper($this->convertirMontantEnLettres($resteAPayer) . ' FRANCS'),
            ],
            'fournisseur' => [
                'nom' => $reglement->fournisseur_nom ?: $reglement->fournisseur?->nom,
            ],
        ]);
    }

    /**
     * PDF de l'imputation comptable d'un règlement
     */
    public function imputationPdf(int $id)
    {
        $reglement = ReglementFournisseur::with(['facture.fournisseur.compteComptable'])->findOrFail($id);
        $facture = $reglement->facture;

        $ecritures = EcritureComptable::where('reglement_id', $id)
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->get();

        if ($ecritures->isEmpty()) {
            abort(404, 'Aucune écriture comptable pour ce règlement');
        }

        $comptesParNumero = \App\Models\CompteComptable::whereIn('numero_compte', $ecritures->pluck('numero_compte')->unique())
            ->pluck('libelle', 'numero_compte')
            ->toArray();

        // PDF d'imputation pour un règlement spécifique : 1 seul bloc (le règlement)
        $blocs = [[
            'label' => 'Règlement',
            'lignes' => $ecritures->values(),
        ]];

        $pdf = Pdf::loadView('pdf.imputation-comptable', [
            'facture' => $facture,
            'reglement' => $reglement,
            'blocs' => $blocs,
            'comptesParNumero' => $comptesParNumero,
            'totalDebit' => $ecritures->sum('debit'),
            'totalCredit' => $ecritures->sum('credit'),
            'user' => auth()->user(),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->stream("imputation-reglement-{$reglement->id}.pdf");
    }

    /**
     * Données d'imputation comptable d'un règlement (JSON pour drawer)
     */
    public function imputationData(int $id): JsonResponse
    {
        $reglement = ReglementFournisseur::with(['facture'])->findOrFail($id);

        // Pas d'imputation comptable pour les règlements en espèces
        if ($reglement->mode_paiement === 'especes') {
            return response()->json([
                'success' => false,
                'message' => 'Aucune imputation comptable pour un règlement en espèces',
            ], 404);
        }

        $ecritures = EcritureComptable::where('reglement_id', $id)
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->get();

        if ($ecritures->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune écriture comptable pour ce règlement',
            ], 404);
        }

        // Pré-charger les libellés des comptes utilisés dans les écritures
        $numerosUtilises = $ecritures->pluck('numero_compte')->unique()->values();
        $comptesParNumero = \App\Models\CompteComptable::whereIn('numero_compte', $numerosUtilises)
            ->pluck('libelle', 'numero_compte')
            ->toArray();

        $ecrituresParDate = $ecritures->groupBy(fn($e) => $e->date_ecriture->format('d/m/Y'));

        $data = [];
        foreach ($ecrituresParDate as $date => $lignes) {
            $data[] = [
                'date' => $date,
                'lignes' => $lignes->map(fn($e) => [
                    'numero_compte' => $e->numero_compte,
                    'compte_libelle' => $comptesParNumero[$e->numero_compte] ?? null,
                    'debit' => (float) $e->debit,
                    'credit' => (float) $e->credit,
                    'libelle' => $e->libelle,
                ])->values()->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'reglement' => [
                'id' => $reglement->id,
                'numero_reglement' => $reglement->numero_reglement,
                'date_reglement' => $reglement->date_reglement?->format('d/m/Y'),
                'montant' => (float) $reglement->montant,
            ],
            'facture' => [
                'id' => $reglement->facture?->id,
                'numero_piece' => $reglement->facture_numero ?: $reglement->facture?->numero_piece,
                'libelle' => $reglement->facture?->libelle,
            ],
            'ecritures' => $data,
            'total_debit' => (float) $ecritures->sum('debit'),
            'total_credit' => (float) $ecritures->sum('credit'),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);
    }

    /**
     * Construire les données communes pour les PDFs
     */
    private function buildPdfData(ReglementFournisseur $reglement): array
    {
        $montant = (float) $reglement->montant;

        $modeLabel = match($reglement->mode_paiement) {
            'especes' => 'Espèces',
            'cheque' => 'Chèque',
            'virement' => 'Virement bancaire',
            'carte' => 'Carte bancaire',
            'mobile_money' => 'Mobile Money',
            default => $reglement->mode_paiement,
        };

        $montantFormate = number_format($montant, 0, ',', ' ') . ' FCFA';
        $montantEnLettres = $this->convertirMontantEnLettres($montant) . ' francs CFA';

        $etablissementLive = \App\Models\Setting::getEtablissement();

        return [
            'reglement' => $reglement,
            'modeLabel' => $modeLabel,
            'montantFormate' => $montantFormate,
            'montantEnLettres' => $montantEnLettres,
            'etablissement' => [
                'nom' => $reglement->etablissement_nom ?: $etablissementLive['nom'],
                'directeur' => $reglement->etablissement_directeur ?: $etablissementLive['directeur'],
                'pays' => $etablissementLive['pays'],
                'adresse' => $etablissementLive['adresse'],
                'telephone' => $etablissementLive['telephone'],
            ],
        ];
    }

    /**
     * Convertir un montant en lettres (français)
     */
    private function convertirMontantEnLettres(float $montant): string
    {
        $montant = (int) $montant;

        if ($montant === 0) return 'zéro';

        $unites = ['', 'un', 'deux', 'trois', 'quatre', 'cinq', 'six', 'sept', 'huit', 'neuf',
                   'dix', 'onze', 'douze', 'treize', 'quatorze', 'quinze', 'seize', 'dix-sept', 'dix-huit', 'dix-neuf'];
        $dizaines = ['', '', 'vingt', 'trente', 'quarante', 'cinquante', 'soixante', 'soixante', 'quatre-vingt', 'quatre-vingt'];

        $convertirBloc = function(int $n) use ($unites, $dizaines): string {
            if ($n === 0) return '';
            if ($n < 20) return $unites[$n];
            if ($n < 100) {
                $d = intdiv($n, 10);
                $u = $n % 10;
                if ($d === 7 || $d === 9) {
                    $u += 10;
                    $base = $dizaines[$d];
                    if ($u === 11 && $d === 7) return $base . ' et onze';
                    return $base . '-' . $unites[$u];
                }
                if ($u === 0) {
                    return $dizaines[$d] . ($d === 8 ? 's' : '');
                }
                if ($u === 1 && $d !== 8) return $dizaines[$d] . ' et un';
                return $dizaines[$d] . '-' . $unites[$u];
            }
            $c = intdiv($n, 100);
            $r = $n % 100;
            $result = ($c === 1 ? 'cent' : $unites[$c] . ' cent');
            if ($r === 0 && $c > 1) $result .= 's';
            if ($r > 0) {
                $convertirBloc2 = function(int $n2) use ($unites, $dizaines): string {
                    if ($n2 < 20) return $unites[$n2];
                    $d = intdiv($n2, 10);
                    $u = $n2 % 10;
                    if ($d === 7 || $d === 9) {
                        $u += 10;
                        $base = $dizaines[$d];
                        if ($u === 11 && $d === 7) return $base . ' et onze';
                        return $base . '-' . $unites[$u];
                    }
                    if ($u === 0) return $dizaines[$d] . ($d === 8 ? 's' : '');
                    if ($u === 1 && $d !== 8) return $dizaines[$d] . ' et un';
                    return $dizaines[$d] . '-' . $unites[$u];
                };
                $result .= ' ' . $convertirBloc2($r);
            }
            return $result;
        };

        $parties = [];

        // Milliards
        $milliards = intdiv($montant, 1000000000);
        $montant %= 1000000000;
        if ($milliards > 0) {
            $parties[] = ($milliards === 1 ? 'un milliard' : $convertirBloc($milliards) . ' milliards');
        }

        // Millions
        $millions = intdiv($montant, 1000000);
        $montant %= 1000000;
        if ($millions > 0) {
            $parties[] = ($millions === 1 ? 'un million' : $convertirBloc($millions) . ' millions');
        }

        // Milliers
        $milliers = intdiv($montant, 1000);
        $montant %= 1000;
        if ($milliers > 0) {
            $parties[] = ($milliers === 1 ? 'mille' : $convertirBloc($milliers) . ' mille');
        }

        // Reste
        if ($montant > 0) {
            $parties[] = $convertirBloc($montant);
        }

        return ucfirst(implode(' ', $parties));
    }

    /**
     * Règles de validation
     */
    private function validationRules(?int $id = null): array
    {
        return [
            'numero_reglement' => [
                'nullable',
                'string',
                'max:50',
                Rule::unique('reglements_fournisseurs')->ignore($id),
            ],
            'date_reglement' => ['required', 'date'],
            'facture_id' => ['required', 'integer', 'exists:factures_fournisseurs,id'],
            'montant' => ['required', 'numeric', 'min:1'],
            'mode_paiement' => ['required', 'string', Rule::in([
                'virement', 'cheque', 'especes', 'mobile_money', 'carte'
            ])],
            'reference' => ['nullable', 'string', 'max:100'],
            'beneficiaire' => ['nullable', 'string', 'max:255'],
            'banque' => ['nullable', 'string', 'max:100'],
            'numero_compte_bancaire' => ['nullable', 'string', 'max:50'],
            'compte_bancaire_id' => ['nullable', 'integer', 'exists:comptes_bancaires,id'],
            'compte_tresorerie_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'compte_credit_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'force_insufficient_balance' => ['nullable', 'boolean'],
            'deduire_aib' => ['nullable', 'boolean'],
            'observations' => ['nullable', 'string'],
            'lignes' => ['nullable', 'array'],
            'lignes.*.compte_id' => ['required_with:lignes', 'integer', 'exists:plan_comptable_ohada,id'],
            'lignes.*.montant' => ['required_with:lignes', 'numeric', 'min:0'],
            'lignes.*.libelle' => ['nullable', 'string', 'max:500'],
        ];
    }

    /**
     * Synchronise les lignes du règlement (multi-fournisseur).
     * Supprime les anciennes et recrée. Si pas de lignes, ne fait rien.
     */
    private function syncReglementLignes(ReglementFournisseur $reglement, array $lignes): void
    {
        $reglement->lignes()->delete();
        foreach ($lignes as $ligne) {
            if (empty($ligne['compte_id'])) continue;
            $reglement->lignes()->create([
                'compte_id' => (int) $ligne['compte_id'],
                'montant' => (float) ($ligne['montant'] ?? 0),
                'libelle' => $ligne['libelle'] ?? null,
            ]);
        }
    }
}
