<?php

namespace App\Http\Controllers;

use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\ReglementFournisseur;
use App\Models\Classe;
use App\Models\CompteComptable;
use App\Models\Banque;
use App\Models\TauxFiscal;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FactureFournisseurController extends Controller
{
    /**
     * Afficher la liste des factures (Vue Inertia)
     */
    public function indexView(Request $request): InertiaResponse
    {
        $query = FactureFournisseur::with(['fournisseur', 'imputation', 'compte']);

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
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
        $sortField = $request->input('sort', 'date');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $facturesPaginated = $query->paginate($perPage);

        // Formater les factures pour la vue
        $factures = $facturesPaginated->map(function ($facture) {
            return [
                'id' => $facture->id,
                'numero' => $facture->numero_piece,
                'numero_piece' => $facture->numero_piece,
                'date_facture' => $facture->date?->format('Y-m-d'),
                'date' => $facture->date?->format('Y-m-d'),
                'reference' => $facture->reference_facture,
                'reference_facture' => $facture->reference_facture,
                'fournisseur' => $facture->fournisseur ? [
                    'id' => $facture->fournisseur->id,
                    'code' => 'FOUR' . str_pad($facture->fournisseur->id, 3, '0', STR_PAD_LEFT),
                    'nom' => $facture->fournisseur->nom,
                ] : null,
                'libelle' => $facture->libelle,
                'montant_ht' => (float) $facture->montant_ht,
                'montant_tva' => (float) $facture->montant_tva,
                'montant_aib' => (float) $facture->montant_reduction,
                'montant_ttc' => (float) $facture->montant_ttc,
                'montant_net' => (float) $facture->montant_net,
                'montant_paye' => (float) $facture->montant_paye,
                'reste_a_payer' => (float) $facture->reste_a_payer,
                'statut' => $facture->statut,
                'statut_paiement' => $this->getStatutPaiement($facture),
                'date_facture_bc' => $facture->date_facture_bc?->format('Y-m-d'),
            ];
        });

        // Récupérer tous les fournisseurs pour le filtre
        $fournisseurs = Fournisseur::select('id', 'nom')
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => ['id' => $f->id, 'nom' => $f->nom]);

        // Imputations : Classe 2, Classe 6, Compte 42
        $imputations = Classe::imputationsFactureFournisseur()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'numero' => $c->code,
                'libelle' => $c->libelle,
                'prefixe_compte' => $c->prefixe_compte,
                'classe' => $c->code,
            ]);

        // Comptes comptables (classes 2, 6 et 42 - tous niveaux)
        $comptes = CompteComptable::where(function ($q) {
                $q->where('numero_compte', 'LIKE', '2%')
                  ->orWhere('numero_compte', 'LIKE', '6%')
                  ->orWhere('numero_compte', 'LIKE', '42%');
            })
            ->whereRaw('LENGTH(numero_compte) >= 2')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->numero_compte,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
                'classe' => substr($c->numero_compte, 0, 1),
            ]);

        // Comptes AIB (4473 et ses sous-comptes)
        $comptesAib = CompteComptable::where('numero_compte', 'LIKE', '4473%')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->numero_compte,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
            ]);

        // Statistiques
        $stats = $this->calculerStats();

        // Taux fiscaux dynamiques
        $tauxAibList = TauxFiscal::getAibActifs();
        $tauxTvaDefaut = TauxFiscal::getTvaParDefaut();

        return Inertia::render('Fournisseurs/Factures/Index', [
            'factures' => $factures,
            'fournisseurs' => $fournisseurs,
            'imputations' => $imputations,
            'comptes' => $comptes,
            'comptesAib' => $comptesAib,
            'tauxAibList' => $tauxAibList,
            'tauxTvaDefaut' => $tauxTvaDefaut,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $facturesPaginated->currentPage(),
                'per_page' => $facturesPaginated->perPage(),
                'total' => $facturesPaginated->total(),
                'last_page' => $facturesPaginated->lastPage(),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Afficher le détail d'une facture (Vue Inertia)
     */
    public function showView(int $id): InertiaResponse
    {
        $facture = FactureFournisseur::with(['fournisseur', 'imputation', 'compte', 'createur', 'validateur'])
            ->findOrFail($id);

        $factureData = [
            'id' => $facture->id,
            'numero' => $facture->numero_piece,
            'numero_piece' => $facture->numero_piece,
            'date_facture' => $facture->date?->format('Y-m-d'),
            'date' => $facture->date?->format('Y-m-d'),
            'date_facture_bc' => $facture->date_facture_bc?->format('Y-m-d'),
            'reference' => $facture->reference_facture,
            'reference_facture' => $facture->reference_facture,
            'fournisseur' => $facture->fournisseur ? [
                'id' => $facture->fournisseur->id,
                'code' => 'FOUR' . str_pad($facture->fournisseur->id, 3, '0', STR_PAD_LEFT),
                'nom' => $facture->fournisseur->nom,
                'contact' => $facture->fournisseur->contact,
                'telephone' => $facture->fournisseur->telephone,
                'email' => $facture->fournisseur->email,
            ] : null,
            'libelle' => $facture->libelle,
            'observations' => $facture->observations,
            // Montants de base
            'montant_facture' => (float) $facture->montant_facture,
            'montant_mo' => (float) $facture->montant_mo,
            'avoir' => (float) $facture->avoir,
            // AIB
            'type_reduction' => $facture->type_reduction,
            'type_reduction_libelle' => $facture->type_reduction_libelle,
            'taux' => (float) $facture->taux,
            'montant_reduction' => (float) $facture->montant_reduction,
            'montant_aib' => (float) $facture->montant_reduction,
            // Montants calculés
            'montant_ht' => (float) $facture->montant_ht,
            'montant_tva' => (float) $facture->montant_tva,
            'montant_ttc' => (float) $facture->montant_ttc,
            'montant_net' => (float) $facture->montant_net,
            // Paiements
            'montant_paye' => (float) $facture->montant_paye,
            'reste_a_payer' => (float) $facture->reste_a_payer,
            'statut' => $facture->statut,
            'statut_paiement' => $this->getStatutPaiement($facture),
            // Relations
            'imputation' => $facture->imputation ? [
                'id' => $facture->imputation->id,
                'code' => $facture->imputation->code,
                'numero' => $facture->imputation->code,
                'libelle' => $facture->imputation->libelle,
                'prefixe_compte' => $facture->imputation->prefixe_compte,
            ] : null,
            'compte' => $facture->compte ? [
                'id' => $facture->compte->id,
                'numero' => $facture->compte->numero_compte,
                'libelle' => $facture->compte->libelle,
            ] : null,
        ];

        // Récupérer les règlements depuis la base de données
        $reglements = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->with(['compteTresorerie'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'date_reglement' => $r->date_reglement?->format('Y-m-d'),
                'montant' => (float) $r->montant,
                'mode_paiement' => $r->mode_paiement,
                'reference' => $r->reference,
                'observations' => $r->observations,
                'compte_bancaire' => $r->compteTresorerie ? [
                    'id' => $r->compteTresorerie->id,
                    'banque' => $r->compteTresorerie->libelle,
                    'libelle' => $r->compteTresorerie->libelle,
                ] : ($r->banque ? [
                    'banque' => $r->banque,
                    'libelle' => $r->banque,
                ] : null),
            ]);

        // Fournisseurs pour le select
        $fournisseurs = Fournisseur::select('id', 'nom')
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => ['id' => $f->id, 'nom' => $f->nom]);

        // Imputations : Classe 2, Classe 6, Compte 42
        $imputations = Classe::imputationsFactureFournisseur()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'numero' => $c->code,
                'libelle' => $c->libelle,
                'prefixe_compte' => $c->prefixe_compte,
                'classe' => $c->code,
            ]);

        // Comptes comptables (classes 2, 6 et 42 - tous niveaux)
        $comptes = CompteComptable::where(function ($q) {
                $q->where('numero_compte', 'LIKE', '2%')
                  ->orWhere('numero_compte', 'LIKE', '6%')
                  ->orWhere('numero_compte', 'LIKE', '42%');
            })
            ->whereRaw('LENGTH(numero_compte) >= 2')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->numero_compte,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
                'classe' => substr($c->numero_compte, 0, 1),
            ]);

        // Comptes AIB (4473 et ses sous-comptes)
        $comptesAib = CompteComptable::where('numero_compte', 'LIKE', '4473%')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->numero_compte,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
            ]);

        return Inertia::render('Fournisseurs/Factures/Show', [
            'facture' => $factureData,
            'reglements' => $reglements,
            'fournisseurs' => $fournisseurs,
            'imputations' => $imputations,
            'comptes' => $comptes,
            'comptesAib' => $comptesAib,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Formulaire de règlement (Vue Inertia)
     */
    public function reglementView(int $id): InertiaResponse
    {
        $facture = FactureFournisseur::with(['fournisseur'])
            ->findOrFail($id);

        $factureData = [
            'id' => $facture->id,
            'numero' => $facture->numero_piece,
            'date_facture' => $facture->date?->format('Y-m-d'),
            'reference' => $facture->reference_facture,
            'fournisseur' => $facture->fournisseur ? [
                'id' => $facture->fournisseur->id,
                'code' => 'FOUR' . str_pad($facture->fournisseur->id, 3, '0', STR_PAD_LEFT),
                'nom' => $facture->fournisseur->nom,
            ] : null,
            'montant_ht' => (float) $facture->montant_ht,
            'montant_tva' => (float) $facture->montant_tva,
            'montant_ttc' => (float) $facture->montant_ttc,
            // Réductions (AIB, etc.)
            'type_reduction' => $facture->type_reduction,
            'type_reduction_libelle' => $facture->type_reduction_libelle,
            'taux' => (float) $facture->taux,
            'montant_reduction' => (float) $facture->montant_reduction,
            'montant_aib' => (float) $facture->montant_reduction, // Alias pour compatibilité
            // Net à payer et reste
            'montant_net' => (float) $facture->montant_net,
            'montant_paye' => (float) $facture->montant_paye,
            'reste_a_payer' => (float) $facture->reste_a_payer,
            'statut' => $facture->statut,
            'statut_paiement' => $this->getStatutPaiement($facture),
        ];

        // Récupérer les règlements existants depuis la base
        $reglements = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->with(['compteTresorerie'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'date_reglement' => $r->date_reglement?->format('Y-m-d'),
                'montant' => (float) $r->montant,
                'mode_paiement' => $r->mode_paiement,
                'reference' => $r->reference,
                'compte_bancaire' => $r->compteTresorerie ? [
                    'id' => $r->compteTresorerie->id,
                    'libelle' => $r->compteTresorerie->libelle,
                ] : ($r->banque ? [
                    'banque' => $r->banque,
                    'libelle' => $r->banque,
                ] : null),
            ]);

        // Banques avec leurs comptes bancaires
        $banques = Banque::with(['comptes' => function($q) {
                $q->orderBy('numero_compte');
            }])
            ->orderBy('nom')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'nom' => $b->nom,
                'comptes' => $b->comptes->map(fn($c) => [
                    'id' => $c->id,
                    'numero_compte' => $c->numero_compte,
                    'solde' => (float) $c->solde,
                ]),
            ]);

        return Inertia::render('Fournisseurs/Factures/Reglement', [
            'facture' => $factureData,
            'reglements' => $reglements,
            'banques' => $banques,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Calculer les statistiques des factures
     */
    private function calculerStats(): array
    {
        $total = FactureFournisseur::count();

        $montantImpaye = FactureFournisseur::whereIn('statut', [
            FactureFournisseur::STATUT_VALIDEE,
            FactureFournisseur::STATUT_BROUILLON,
        ])->sum('montant_net');

        $montantPartiel = FactureFournisseur::where('statut', FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE)
            ->sum('reste_a_payer');

        $montantPaye = FactureFournisseur::sum('montant_paye');

        return [
            'total' => $total,
            'montant_impaye' => (float) $montantImpaye,
            'montant_partiel' => (float) $montantPartiel,
            'montant_paye' => (float) $montantPaye,
        ];
    }

    /**
     * Déterminer le statut de paiement simplifié
     */
    private function getStatutPaiement(FactureFournisseur $facture): string
    {
        if ($facture->statut === FactureFournisseur::STATUT_PAYEE) {
            return 'payee';
        }
        if ($facture->statut === FactureFournisseur::STATUT_PARTIELLEMENT_PAYEE) {
            return 'partielle';
        }
        return 'impayee';
    }

    /**
     * Lister les factures d'un fournisseur (API)
     */
    public function index(Request $request): JsonResponse
    {
        $query = FactureFournisseur::with(['fournisseur', 'imputation', 'compte']);

        // Filtre par fournisseur
        if ($request->filled('fournisseur_id')) {
            $query->where('fournisseur_id', $request->fournisseur_id);
        }

        // Filtre par statut
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
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
        $sortField = $request->input('sort', 'date');
        $sortOrder = $request->input('order', 'desc');
        $query->orderBy($sortField, $sortOrder);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $factures = $query->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $factures->map(fn($f) => $f->toApiArray()),
            'pagination' => [
                'current_page' => $factures->currentPage(),
                'per_page' => $factures->perPage(),
                'total' => $factures->total(),
                'last_page' => $factures->lastPage(),
            ],
        ]);
    }

    /**
     * Afficher une facture (API)
     */
    public function show(int $id): JsonResponse
    {
        $facture = FactureFournisseur::with(['fournisseur', 'imputation', 'compte', 'createur', 'validateur'])
            ->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => $facture->toApiArray(),
        ]);
    }

    /**
     * Créer une nouvelle facture (API)
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

        try {
            DB::beginTransaction();

            // Générer le numéro de pièce si non fourni
            $numeroPiece = $request->numero_piece ?: FactureFournisseur::genererNumeroPiece();

            // Vérifier l'unicité du numéro
            if (FactureFournisseur::where('numero_piece', $numeroPiece)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ce numéro de pièce existe déjà',
                    'errors' => ['numero_piece' => ['Ce numéro de pièce existe déjà']],
                ], 422);
            }

            $facture = FactureFournisseur::create([
                'numero_piece' => $numeroPiece,
                'date' => $request->date,
                'reference_facture' => $request->reference_facture,
                'fournisseur_id' => $request->fournisseur_id,
                'imputation_id' => $request->imputation_id,
                'compte_id' => $request->compte_id,
                'libelle' => $request->libelle,
                'montant_facture' => $request->montant_facture ?? 0,
                'montant_mo' => $request->montant_mo ?? 0,
                'avoir' => $request->avoir ?? 0,
                'type_reduction' => $request->type_reduction,
                'taux' => $request->taux ?? 0,
                'assujetti_tva' => $request->assujetti_tva ?? true,
                'taux_tva' => $request->taux_tva ?? TauxFiscal::getTvaParDefaut(),
                'date_facture_bc' => $request->date_facture_bc,
                'observations' => $request->observations,
                'metadata' => $request->metadata,
                'statut' => 'brouillon',
                'created_by' => auth()->id(),
            ]);

            DB::commit();

            $facture->load(['fournisseur', 'imputation', 'compte']);

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès',
                'data' => $facture->toApiArray(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour une facture (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $facture = FactureFournisseur::findOrFail($id);

        // Vérifier si la facture est modifiable
        if (!$facture->est_modifiable) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture ne peut plus être modifiée',
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

        try {
            DB::beginTransaction();

            $facture->update([
                'numero_piece' => $request->numero_piece ?? $facture->numero_piece,
                'date' => $request->date ?? $facture->date,
                'reference_facture' => $request->reference_facture,
                'fournisseur_id' => $request->fournisseur_id ?? $facture->fournisseur_id,
                'imputation_id' => $request->imputation_id,
                'compte_id' => $request->compte_id,
                'libelle' => $request->libelle ?? $facture->libelle,
                'montant_facture' => $request->montant_facture ?? $facture->montant_facture,
                'montant_mo' => $request->montant_mo ?? $facture->montant_mo,
                'avoir' => $request->avoir ?? $facture->avoir,
                'type_reduction' => $request->type_reduction,
                'taux' => $request->taux ?? $facture->taux,
                'assujetti_tva' => $request->assujetti_tva ?? $facture->assujetti_tva,
                'taux_tva' => $request->taux_tva ?? $facture->taux_tva,
                'date_facture_bc' => $request->date_facture_bc,
                'observations' => $request->observations,
                'metadata' => $request->metadata ?? $facture->metadata,
            ]);

            DB::commit();

            $facture->load(['fournisseur', 'imputation', 'compte']);

            return response()->json([
                'success' => true,
                'message' => 'Facture modifiée avec succès',
                'data' => $facture->toApiArray(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer une facture (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $facture = FactureFournisseur::findOrFail($id);

        // Vérifier si la facture a des règlements
        $nbReglements = ReglementFournisseur::where('facture_id', $id)->count();
        if ($nbReglements > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer cette facture car elle fait l'objet de {$nbReglements} règlement(s). Veuillez d'abord supprimer les règlements associés.",
            ], 422);
        }

        // Vérifier si la facture peut être supprimée
        if (!$facture->est_modifiable) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture ne peut pas être supprimée',
            ], 422);
        }

        try {
            $facture->delete();

            return response()->json([
                'success' => true,
                'message' => 'Facture supprimée avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valider une facture (API)
     */
    public function valider(int $id): JsonResponse
    {
        $facture = FactureFournisseur::findOrFail($id);

        if (!$facture->peut_etre_validee) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture ne peut pas être validée',
            ], 422);
        }

        try {
            $facture->valider(auth()->id());

            return response()->json([
                'success' => true,
                'message' => 'Facture validée avec succès',
                'data' => $facture->fresh()->toApiArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la validation de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Annuler une facture (API)
     */
    public function annuler(int $id): JsonResponse
    {
        $facture = FactureFournisseur::findOrFail($id);

        if ($facture->statut === FactureFournisseur::STATUT_PAYEE) {
            return response()->json([
                'success' => false,
                'message' => 'Une facture payée ne peut pas être annulée',
            ], 422);
        }

        try {
            $facture->annuler();

            return response()->json([
                'success' => true,
                'message' => 'Facture annulée avec succès',
                'data' => $facture->fresh()->toApiArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'annulation de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Marquer une facture comme soldée (API)
     */
    public function solder(int $id): JsonResponse
    {
        $facture = FactureFournisseur::findOrFail($id);

        // Vérifier que la facture n'est pas déjà soldée ou annulée
        if ($facture->statut === FactureFournisseur::STATUT_PAYEE) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture est déjà soldée',
            ], 422);
        }

        if ($facture->statut === FactureFournisseur::STATUT_ANNULEE) {
            return response()->json([
                'success' => false,
                'message' => 'Une facture annulée ne peut pas être soldée',
            ], 422);
        }

        try {
            // Marquer comme payée (soldée)
            $facture->statut = FactureFournisseur::STATUT_PAYEE;
            $facture->reste_a_payer = 0;
            $facture->save();

            return response()->json([
                'success' => true,
                'message' => 'Facture marquée comme soldée avec succès',
                'data' => $facture->fresh()->toApiArray(),
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors du marquage de la facture',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer un numéro de pièce (API)
     */
    public function genererNumero(): JsonResponse
    {
        $prochainNumero = FactureFournisseur::genererNumeroPiece();

        return response()->json([
            'success' => true,
            'numero_piece' => $prochainNumero,
            'prochain_numero' => $prochainNumero,
        ]);
    }

    /**
     * Vérifier un numéro de pièce (API)
     * Retourne si le numéro est valide, s'il y a un saut de séquence, etc.
     */
    public function verifierNumeroPiece(Request $request): JsonResponse
    {
        $numeroPiece = $request->get('numero_piece', '');
        $prochainNumero = FactureFournisseur::genererNumeroPiece();

        // Vérifier si le numéro existe déjà
        $existe = FactureFournisseur::where('numero_piece', $numeroPiece)->exists();

        if ($existe) {
            return response()->json([
                'success' => false,
                'valide' => false,
                'message' => 'Ce numéro de pièce existe déjà',
                'prochain_numero' => $prochainNumero,
                'erreur' => 'doublon',
            ]);
        }

        // Vérifier le format attendu (PC/YYY/XXXX)
        $formatValide = preg_match('/^PC\/\d{3}\/\d{4}$/', $numeroPiece);

        if (!$formatValide) {
            return response()->json([
                'success' => true,
                'valide' => true,
                'format_standard' => false,
                'message' => 'Format personnalisé accepté',
                'prochain_numero' => $prochainNumero,
                'avertissement' => 'Ce numéro ne suit pas le format standard (PC/YYY/XXXX). Cela pourrait compliquer le suivi séquentiel.',
            ]);
        }

        // Extraire la séquence du numéro saisi
        $parties = explode('/', $numeroPiece);
        $sequenceSaisie = (int) ($parties[2] ?? 0);

        // Extraire la séquence attendue
        $partiesProchain = explode('/', $prochainNumero);
        $sequenceAttendue = (int) ($partiesProchain[2] ?? 0);

        // Vérifier l'année
        $anneeSaisie = $parties[1] ?? '';
        $anneeAttendue = $partiesProchain[1] ?? '';

        if ($anneeSaisie !== $anneeAttendue) {
            return response()->json([
                'success' => true,
                'valide' => true,
                'format_standard' => true,
                'prochain_numero' => $prochainNumero,
                'avertissement' => "L'année du numéro ($anneeSaisie) ne correspond pas à l'année en cours ($anneeAttendue).",
            ]);
        }

        // Vérifier la séquence
        if ($sequenceSaisie < $sequenceAttendue) {
            // Numéro inférieur à ce qui est attendu
            return response()->json([
                'success' => true,
                'valide' => true,
                'format_standard' => true,
                'prochain_numero' => $prochainNumero,
                'avertissement' => "Ce numéro ($sequenceSaisie) est inférieur au prochain attendu ($sequenceAttendue). Vérifiez qu'il n'y a pas de doublon.",
            ]);
        } elseif ($sequenceSaisie > $sequenceAttendue) {
            // Saut de séquence
            $saut = $sequenceSaisie - $sequenceAttendue;
            return response()->json([
                'success' => true,
                'valide' => true,
                'format_standard' => true,
                'prochain_numero' => $prochainNumero,
                'avertissement' => "Attention : saut de séquence détecté. Prochain numéro attendu: $sequenceAttendue, numéro saisi: $sequenceSaisie (saut de $saut).",
                'saut_sequence' => $saut,
            ]);
        }

        // Numéro parfait
        return response()->json([
            'success' => true,
            'valide' => true,
            'format_standard' => true,
            'prochain_numero' => $prochainNumero,
            'message' => 'Numéro de pièce valide et séquentiel',
        ]);
    }

    /**
     * Statistiques des factures (API)
     */
    public function stats(Request $request): JsonResponse
    {
        $fournisseurId = $request->filled('fournisseur_id') ? (int) $request->fournisseur_id : null;
        $stats = FactureFournisseur::getStatistiques($fournisseurId);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Règles de validation
     */
    private function validationRules(?int $id = null): array
    {
        $numeroPieceRules = ['nullable', 'string', 'max:50'];

        // Unicité uniquement à la création
        if (!$id) {
            $numeroPieceRules[] = Rule::unique('factures_fournisseurs');
        }

        return [
            'numero_piece' => $numeroPieceRules,
            'date' => ['required', 'date'],
            'reference_facture' => ['nullable', 'string', 'max:100'],
            'fournisseur_id' => ['required', 'integer', 'exists:fournisseurs,id'],
            'imputation_id' => ['nullable', 'integer', 'exists:classes,id'],
            'compte_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'libelle' => ['required', 'string', 'max:500'],
            'montant_facture' => ['required', 'numeric', 'min:0'],
            'montant_mo' => ['nullable', 'numeric', 'min:0'],
            'avoir' => ['nullable', 'numeric', 'min:0'],
            'type_reduction' => ['nullable', 'string'],
            'taux' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'assujetti_tva' => ['nullable', 'boolean'],
            'taux_tva' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'date_facture_bc' => ['nullable', 'date'],
            'observations' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
