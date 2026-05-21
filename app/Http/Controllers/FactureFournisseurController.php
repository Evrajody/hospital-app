<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\FactureFournisseur;
use App\Models\Fournisseur;
use App\Models\ReglementFournisseur;
use App\Models\EcritureComptable;
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
        $query = FactureFournisseur::with(['fournisseur', 'imputation', 'compte'])
            ->withCount('imputations');

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

        // Tri (mapping des noms Vue → colonnes SQL)
        $sortMapping = [
            'numero' => 'numero_piece',
            'date_facture' => 'date',
            'date_facture_bc' => 'date_facture_bc',
            'reference' => 'reference_facture',
            'libelle' => 'libelle',
            'montant_ttc' => 'montant_ttc',
            'montant_net' => 'montant_net',
            'montant_paye' => 'montant_paye',
            'statut_paiement' => 'statut_paiement',
        ];
        $sortKey = $request->input('sort', 'date');
        $sortOrder = $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortKey === 'fournisseur') {
            $query->leftJoin('fournisseurs', 'factures_fournisseurs.fournisseur_id', '=', 'fournisseurs.id')
                  ->orderBy('fournisseurs.nom', $sortOrder)
                  ->select('factures_fournisseurs.*');
        } else {
            $sortField = $sortMapping[$sortKey] ?? 'date';
            $query->orderBy($sortField, $sortOrder);
        }

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
                    'nom' => $facture->fournisseur_nom ?: $facture->fournisseur->nom,
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
                'imputation_id' => $facture->imputation_id,
                'compte_id' => $facture->compte_id,
                'imputations_count' => (int) ($facture->imputations_count ?? 0),
            ];
        });

        // Récupérer tous les fournisseurs pour le filtre
        $fournisseurs = Fournisseur::select('id', 'nom')
            ->orderBy('nom')
            ->get()
            ->map(fn($f) => ['id' => $f->id, 'nom' => $f->nom]);

        ['imputations' => $imputations, 'comptes' => $comptes] = $this->getImputationsAndComptes();

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
        $facture = FactureFournisseur::with(['fournisseur', 'imputation', 'compte', 'createur', 'validateur', 'imputations.compte'])
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
                'nom' => $facture->fournisseur_nom ?: $facture->fournisseur->nom,
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
            // Imputations multiples (nouvelle structure)
            'imputations' => $facture->imputations->map(fn($imp) => [
                'id' => $imp->id,
                'compte_id' => $imp->compte_id,
                'nature' => $imp->nature ?? 'debit',
                'libelle' => $imp->libelle,
                'montant' => (float) $imp->montant,
                'compte' => $imp->compte ? [
                    'id' => $imp->compte->id,
                    'numero' => $imp->compte->numero_compte,
                    'libelle' => $imp->compte->libelle,
                ] : null,
            ])->values()->toArray(),
            // État des écritures comptables (pour bouton + badge)
            'has_imputation' => (bool) ($facture->imputation_id || $facture->compte_id || $facture->imputations->isNotEmpty()),
            'has_ecritures_facture' => \App\Models\EcritureComptable::where('facture_id', $id)
                ->where('type', \App\Models\EcritureComptable::TYPE_FACTURE)
                ->exists(),
        ];

        // Récupérer les règlements depuis la base de données
        $reglementsIds = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->pluck('id');
        $reglementsAvecEcritures = \App\Models\EcritureComptable::whereIn('reglement_id', $reglementsIds)
            ->pluck('reglement_id')
            ->unique()
            ->flip();

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
                'deduire_aib' => (bool) $r->deduire_aib,
                'has_ecritures' => $reglementsAvecEcritures->has($r->id),
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

        ['imputations' => $imputations, 'comptes' => $comptes] = $this->getImputationsAndComptes();

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
        $facture = FactureFournisseur::with(['fournisseur', 'imputation', 'compte', 'imputations.compte'])
            ->findOrFail($id);

        // Imputations crédit (fournisseurs 401/481) saisies sur la facture
        $imputationsCredits = $facture->imputations
            ->filter(fn($i) => ($i->nature ?? 'debit') === 'credit')
            ->values();

        // Solde par compte crédit : montant facture - somme des règlements ciblant ce compte
        $reglementsParCompte = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->whereNotNull('compte_credit_id')
            ->selectRaw('compte_credit_id, SUM(montant) as total_paye, SUM(COALESCE(montant_aib_deduit,0)) as total_aib')
            ->groupBy('compte_credit_id')
            ->get()
            ->keyBy('compte_credit_id');

        // Modèle "TVA pour compte" : on ne règle au fournisseur que la portion HT
        // (la TVA reste à l'État). Le HT par compte = TTC_compte / (1 + taux_tva/100).
        $tauxTva = (float) ($facture->assujetti_tva ? $facture->taux_tva : 0);
        $coefTva = 1 + ($tauxTva / 100);

        $imputationsCreditsData = $imputationsCredits->map(function ($imp) use ($reglementsParCompte, $coefTva) {
            $reg = $reglementsParCompte->get($imp->compte_id);
            $totalPaye = $reg ? (float) $reg->total_paye : 0;
            $totalAib = $reg ? (float) $reg->total_aib : 0;
            $montantTtc = (float) $imp->montant;
            // HT du compte = TTC / (1 + taux_tva)
            $montantHt = $coefTva > 0 ? round($montantTtc / $coefTva, 2) : $montantTtc;
            // Reste à payer en HT = HT − somme déjà payée − AIB déjà retenue sur ce compte
            $reste = max(0, $montantHt - $totalPaye - $totalAib);
            return [
                'id' => $imp->id,
                'compte_id' => $imp->compte_id,
                'numero_compte' => $imp->compte?->numero_compte ?? '-',
                'libelle_compte' => $imp->compte?->libelle ?? '-',
                'libelle_imputation' => $imp->libelle ?? '',
                'montant' => $montantTtc,
                'montant_ht' => $montantHt,
                'total_paye' => $totalPaye,
                'total_aib' => $totalAib,
                'reste_a_payer' => $reste,
            ];
        })->values()->toArray();

        $factureData = [
            'id' => $facture->id,
            'numero' => $facture->numero_piece,
            'date_facture' => $facture->date?->format('Y-m-d'),
            'reference' => $facture->reference_facture,
            'fournisseur' => $facture->fournisseur ? [
                'id' => $facture->fournisseur->id,
                'code' => 'FOUR' . str_pad($facture->fournisseur->id, 3, '0', STR_PAD_LEFT),
                'nom' => $facture->fournisseur_nom ?: $facture->fournisseur->nom,
            ] : null,
            'montant_ht' => (float) $facture->montant_ht,
            'montant_facture' => (float) $facture->montant_facture,
            'montant_mo' => (float) $facture->montant_mo,
            'montant_tva' => (float) $facture->montant_tva,
            'montant_ttc' => (float) $facture->montant_ttc,
            // Réductions (AIB, etc.)
            'type_reduction' => $facture->type_reduction,
            'type_reduction_libelle' => $facture->type_reduction_libelle,
            'taux' => (float) $facture->taux,
            'montant_reduction' => (float) $facture->montant_reduction,
            'montant_aib' => (float) $facture->montant_reduction,
            // Net à payer et reste
            'montant_net' => (float) $facture->montant_net,
            'montant_paye' => (float) $facture->montant_paye,
            'reste_a_payer' => (float) $facture->reste_a_payer,
            'statut' => $facture->statut,
            'statut_paiement' => $this->getStatutPaiement($facture),
            // Imputation (pour le modal après règlement)
            'imputation' => $facture->imputation ? true : false,
            'compte' => $facture->compte ? true : false,
            // Comptes crédit disponibles + soldes par compte
            'imputations_credits' => $imputationsCreditsData,
        ];

        // Récupérer les règlements existants depuis la base
        $reglements = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->with(['compteTresorerie', 'compteCredit'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => [
                'id' => $r->id,
                'date_reglement' => $r->date_reglement?->format('Y-m-d'),
                'montant' => (float) $r->montant,
                'mode_paiement' => $r->mode_paiement,
                'reference' => $r->reference,
                'beneficiaire' => $r->beneficiaire,
                'deduire_aib' => (bool) $r->deduire_aib,
                'montant_aib_deduit' => (float) $r->montant_aib_deduit,
                'compte_credit_id' => $r->compte_credit_id,
                'compte_credit' => $r->compteCredit ? [
                    'id' => $r->compteCredit->id,
                    'numero' => $r->compteCredit->numero_compte,
                    'libelle' => $r->compteCredit->libelle,
                ] : null,
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

        $beneficiaires = \App\Models\ReglementFournisseur::whereNotNull('beneficiaire')
            ->where('beneficiaire', '!=', '')
            ->distinct()
            ->orderBy('beneficiaire')
            ->pluck('beneficiaire')
            ->values();

        return Inertia::render('Fournisseurs/Factures/Reglement', [
            'facture' => $factureData,
            'reglements' => $reglements,
            'banques' => $banques,
            'beneficiaires' => $beneficiaires,
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

        // Tri (mapping des noms Vue → colonnes SQL)
        $sortMapping = [
            'numero' => 'numero_piece',
            'date_facture' => 'date',
            'date_facture_bc' => 'date_facture_bc',
            'reference' => 'reference_facture',
            'libelle' => 'libelle',
            'montant_ttc' => 'montant_ttc',
            'montant_net' => 'montant_net',
            'montant_paye' => 'montant_paye',
            'statut_paiement' => 'statut_paiement',
        ];
        $sortKey = $request->input('sort', 'date');
        $sortOrder = $request->input('order', 'desc') === 'asc' ? 'asc' : 'desc';

        if ($sortKey === 'fournisseur') {
            $query->leftJoin('fournisseurs', 'factures_fournisseurs.fournisseur_id', '=', 'fournisseurs.id')
                  ->orderBy('fournisseurs.nom', $sortOrder)
                  ->select('factures_fournisseurs.*');
        } else {
            $sortField = $sortMapping[$sortKey] ?? 'date';
            $query->orderBy($sortField, $sortOrder);
        }

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
        $facture = FactureFournisseur::with(['fournisseur', 'imputation', 'compte', 'createur', 'validateur', 'imputations'])
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

            // Snapshot du fournisseur
            $fournisseur = Fournisseur::find($request->fournisseur_id);

            $facture = FactureFournisseur::create([
                'numero_piece' => $numeroPiece,
                'date' => $request->date,
                'reference_facture' => $request->reference_facture,
                'fournisseur_id' => $request->fournisseur_id,
                'fournisseur_nom' => $fournisseur?->nom,
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
                'created_by_name' => auth()->user()->name,
            ]);

            $facture->load(['fournisseur', 'imputation', 'compte']);

            $this->syncImputationsMultiples($facture, $request->input('imputations', []));

            // Pas de génération auto des écritures à la création
            // (le frontend demandera explicitement via creerImputation si l'utilisateur veut)
            $facture->load(['imputations.compte', 'fournisseur.compteComptable']);

            // Vérifier si la facture a une imputation
            $hasImputation = $facture->imputation_id || $facture->compte_id || $facture->imputations->isNotEmpty();

            DB::commit();

            ActivityLog::log('create', 'facture_fournisseur', "Création de la facture {$facture->numero_piece}", $facture, ['numero_piece' => $facture->numero_piece]);

            return response()->json([
                'success' => true,
                'message' => 'Facture créée avec succès',
                'data' => $facture->toApiArray(),
                'has_imputation' => $hasImputation,
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

            // Re-snapshot si le fournisseur change
            $fournisseurId = $request->fournisseur_id ?? $facture->fournisseur_id;
            $fournisseurNom = $facture->fournisseur_nom;
            if ($request->fournisseur_id && $request->fournisseur_id != $facture->fournisseur_id) {
                $fournisseurNom = Fournisseur::find($request->fournisseur_id)?->nom;
            }

            $facture->update([
                'numero_piece' => $request->numero_piece ?? $facture->numero_piece,
                'date' => $request->date ?? $facture->date,
                'reference_facture' => $request->reference_facture,
                'fournisseur_id' => $fournisseurId,
                'fournisseur_nom' => $fournisseurNom,
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

            if ($request->has('imputations')) {
                $this->syncImputationsMultiples($facture, $request->input('imputations', []));
            }

            // Re-sync uniquement si des écritures de type facture existaient déjà
            // (sinon, l'utilisateur doit déclencher manuellement via creerImputation).
            $facture->load(['imputations.compte', 'fournisseur.compteComptable']);
            $avaitEcritures = EcritureComptable::where('facture_id', $facture->id)
                ->where('type', EcritureComptable::TYPE_FACTURE)
                ->exists();
            if ($avaitEcritures) {
                EcritureComptable::where('facture_id', $facture->id)
                    ->where('type', EcritureComptable::TYPE_FACTURE)
                    ->delete();
                EcritureComptable::creerEcrituresFacture($facture);
            }

            DB::commit();

            ActivityLog::log('update', 'facture_fournisseur', "Modification de la facture {$facture->numero_piece}", $facture, ['numero_piece' => $facture->numero_piece]);

            $facture->load(['fournisseur', 'imputation', 'compte']);

            $hasImputation = $facture->imputation_id || $facture->compte_id || $facture->imputations->isNotEmpty();

            return response()->json([
                'success' => true,
                'message' => 'Facture modifiée avec succès',
                'data' => $facture->toApiArray(),
                'has_imputation' => $hasImputation,
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
            $numeroPiece = $facture->numero_piece;
            $facture->delete();

            ActivityLog::log('delete', 'facture_fournisseur', "Suppression de la facture {$numeroPiece}", null, ['numero_piece' => $numeroPiece]);

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

            ActivityLog::log('validate', 'facture_fournisseur', "Validation de la facture {$facture->numero_piece}", $facture, ['numero_piece' => $facture->numero_piece]);

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

            ActivityLog::log('cancel', 'facture_fournisseur', "Annulation de la facture {$facture->numero_piece}", $facture, ['numero_piece' => $facture->numero_piece]);

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

            ActivityLog::log('settle', 'facture_fournisseur', "Solde de la facture {$facture->numero_piece}", $facture, ['numero_piece' => $facture->numero_piece]);

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
     * Créer les écritures d'imputation comptable pour une facture (API)
     */
    public function creerImputation(int $id): JsonResponse
    {
        $facture = FactureFournisseur::with(['fournisseur.compteComptable', 'compte', 'imputations.compte'])
            ->findOrFail($id);

        // 1. Vérifier que des imputations existent
        if ($facture->imputations->isEmpty() && !$facture->compte_id) {
            return response()->json([
                'success' => false,
                'reason' => 'missing',
                'message' => "Les imputations comptables n'existent pas pour cette facture. Vous devez d'abord les saisir.",
            ], 422);
        }

        // 2. Vérifier que les imputations englobent le montant de la facture
        // (uniquement pour la nouvelle structure multi-imputations, pas pour le legacy compte_id seul)
        if ($facture->imputations->isNotEmpty()) {
            $totalDebits = (float) $facture->imputations->where('nature', 'debit')->sum('montant');
            $totalCredits = (float) $facture->imputations->where('nature', 'credit')->sum('montant');
            $montantHt = (float) $facture->montant_ht;
            $montantTtc = (float) $facture->montant_ttc;

            $manques = [];
            if (abs($totalDebits - $montantHt) > 0.5) {
                $ecart = number_format($montantHt - $totalDebits, 0, ',', ' ');
                $manques[] = "le total des débits (" . number_format($totalDebits, 0, ',', ' ') . " XOF) ne couvre pas le montant HT (" . number_format($montantHt, 0, ',', ' ') . " XOF) — écart : {$ecart} XOF";
            }
            if (abs($totalCredits - $montantTtc) > 0.5) {
                $ecart = number_format($montantTtc - $totalCredits, 0, ',', ' ');
                $manques[] = "le total des crédits (" . number_format($totalCredits, 0, ',', ' ') . " XOF) ne couvre pas le montant TTC (" . number_format($montantTtc, 0, ',', ' ') . " XOF) — écart : {$ecart} XOF";
            }

            if (!empty($manques)) {
                return response()->json([
                    'success' => false,
                    'reason' => 'incomplete',
                    'message' => "Les imputations comptables sont incomplètes : " . implode(' ; ', $manques) . ".",
                ], 422);
            }
        }

        try {
            DB::beginTransaction();

            // 1. Re-générer les écritures de la facture
            \App\Models\EcritureComptable::where('facture_id', $id)
                ->where('type', \App\Models\EcritureComptable::TYPE_FACTURE)
                ->delete();
            \App\Models\EcritureComptable::creerEcrituresFacture($facture);

            // 2. Re-générer les écritures de TOUS les règlements non-annulés et non-espèces de la facture
            $reglements = ReglementFournisseur::with(['compteTresorerie', 'lignes.compte'])
                ->where('facture_id', $id)
                ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
                ->where('mode_paiement', '!=', 'especes')
                ->get();

            $nbReglements = 0;
            foreach ($reglements as $reglement) {
                \App\Models\EcritureComptable::supprimerEcrituresReglement($reglement->id);
                \App\Models\EcritureComptable::creerEcrituresReglement($reglement, $facture);
                $nbReglements++;
            }

            DB::commit();

            $msg = 'Imputation comptable créée avec succès';
            if ($nbReglements > 0) {
                $msg .= " ({$nbReglements} règlement(s) inclus)";
            }

            return response()->json([
                'success' => true,
                'message' => $msg,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création de l\'imputation',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Générer le PDF d'imputation comptable d'une facture
     */
    public function imputationPdf(int $id)
    {
        $facture = FactureFournisseur::with(['fournisseur.compteComptable', 'compte', 'imputation', 'imputations.compte'])
            ->findOrFail($id);

        $ecritures = \App\Models\EcritureComptable::where('facture_id', $id)
            ->orderByRaw("CASE type WHEN 'facture' THEN 0 ELSE 1 END")
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->get();

        if ($ecritures->isEmpty()) {
            abort(404, 'Aucune écriture comptable pour cette facture');
        }

        // Pré-charger les libellés des comptes
        $comptesParNumero = \App\Models\CompteComptable::whereIn('numero_compte', $ecritures->pluck('numero_compte')->unique())
            ->pluck('libelle', 'numero_compte')
            ->toArray();

        // 2 blocs : facture (1 bloc) puis tous les règlements (1 bloc)
        $factureLignes = $ecritures->where('type', \App\Models\EcritureComptable::TYPE_FACTURE);
        $reglementLignes = $ecritures->where('type', \App\Models\EcritureComptable::TYPE_REGLEMENT);

        $blocs = [];
        if ($factureLignes->isNotEmpty()) {
            $blocs[] = [
                'label' => null,
                'lignes' => $factureLignes->values(),
            ];
        }
        if ($reglementLignes->isNotEmpty()) {
            $blocs[] = [
                'label' => null,
                'lignes' => $reglementLignes->values(),
            ];
        }

        $totalDebit = $ecritures->sum('debit');
        $totalCredit = $ecritures->sum('credit');

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.imputation-comptable', [
            'facture' => $facture,
            'blocs' => $blocs,
            'comptesParNumero' => $comptesParNumero,
            'totalDebit' => $totalDebit,
            'totalCredit' => $totalCredit,
            'user' => auth()->user(),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);
        $pdf->setPaper('a4', 'landscape');

        return $pdf->download("imputation-comptable-{$facture->id}.pdf");
    }

    /**
     * Récupérer les données d'imputation comptable (JSON)
     */
    public function imputationData(int $id): JsonResponse
    {
        $facture = FactureFournisseur::with(['fournisseur.compteComptable', 'compte', 'imputation', 'imputations.compte'])
            ->findOrFail($id);

        // Toutes les écritures liées à cette facture : enregistrement (type=facture) + règlements (type=reglement)
        $ecritures = \App\Models\EcritureComptable::where('facture_id', $id)
            ->orderByRaw("CASE type WHEN 'facture' THEN 0 ELSE 1 END")
            ->orderBy('date_ecriture')
            ->orderBy('id')
            ->get();

        // Lazy generation : créer les écritures facture si manquantes (anciennes données)
        $factureEcritures = $ecritures->where('type', \App\Models\EcritureComptable::TYPE_FACTURE);
        $hasFactureEcritures = $factureEcritures->isNotEmpty();
        $hasImputationsSource = $facture->imputations->isNotEmpty() || $facture->compte_id;

        // Détecter une ligne AIB indésirable au niveau facture (vestige du modèle 🅱️)
        // → l'AIB doit être au règlement uniquement, pas à la facture
        $aibCompteAttendu = $facture->type_reduction ?: '44731';
        $aibPossibles = ['44731', '4473', $aibCompteAttendu];
        $hasUnwantedAibAtFacture = $factureEcritures
            ->whereIn('numero_compte', array_unique($aibPossibles))
            ->isNotEmpty();

        $shouldRegenerate = (!$hasFactureEcritures && $hasImputationsSource)
            || ($hasImputationsSource && $hasUnwantedAibAtFacture);

        if ($shouldRegenerate) {
            \App\Models\EcritureComptable::where('facture_id', $id)
                ->where('type', \App\Models\EcritureComptable::TYPE_FACTURE)
                ->delete();
            \App\Models\EcritureComptable::creerEcrituresFacture($facture);
            $ecritures = \App\Models\EcritureComptable::where('facture_id', $id)
                ->orderByRaw("CASE type WHEN 'facture' THEN 0 ELSE 1 END")
                ->orderBy('date_ecriture')
                ->orderBy('id')
                ->get();
        }

        if ($ecritures->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Aucune écriture comptable pour cette facture',
            ], 404);
        }

        // Pré-charger les libellés des comptes utilisés dans les écritures
        $numerosUtilises = $ecritures->pluck('numero_compte')->unique()->values();
        $comptesParNumero = \App\Models\CompteComptable::whereIn('numero_compte', $numerosUtilises)
            ->pluck('libelle', 'numero_compte')
            ->toArray();

        // 2 blocs : la facture (1 bloc), puis tous les règlements (1 seul bloc)
        $factureLignes = $ecritures->where('type', \App\Models\EcritureComptable::TYPE_FACTURE);
        $reglementLignes = $ecritures->where('type', \App\Models\EcritureComptable::TYPE_REGLEMENT);

        $mapLigne = fn($e) => [
            'date' => $e->date_ecriture->format('d/m/Y'),
            'numero_compte' => $e->numero_compte,
            'compte_libelle' => $comptesParNumero[$e->numero_compte] ?? null,
            'debit' => (float) $e->debit,
            'credit' => (float) $e->credit,
            'libelle' => $e->libelle,
        ];

        $data = [];
        if ($factureLignes->isNotEmpty()) {
            $data[] = [
                'date' => $factureLignes->first()->date_ecriture->format('d/m/Y'),
                'label' => null,
                'lignes' => $factureLignes->map($mapLigne)->values()->toArray(),
            ];
        }
        if ($reglementLignes->isNotEmpty()) {
            $data[] = [
                'date' => $reglementLignes->first()->date_ecriture->format('d/m/Y'),
                'label' => null,
                'lignes' => $reglementLignes->map($mapLigne)->values()->toArray(),
            ];
        }

        return response()->json([
            'success' => true,
            'facture' => [
                'id' => $facture->id,
                'numero_piece' => $facture->numero_piece,
                'date' => $facture->date?->format('d/m/Y'),
                'fournisseur' => $facture->fournisseur_nom ?: $facture->fournisseur?->nom,
                'libelle' => $facture->libelle,
            ],
            'ecritures' => $data,
            'total_debit' => (float) $ecritures->sum('debit'),
            'total_credit' => (float) $ecritures->sum('credit'),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);
    }

    /**
     * Données pour le drawer "État de Règlement Facture" (JSON)
     */
    public function etatReglementData(int $id): JsonResponse
    {
        $facture = FactureFournisseur::with(['fournisseur.compteComptable'])
            ->findOrFail($id);

        $reglements = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->orderBy('date_reglement')
            ->orderBy('id')
            ->get();

        $totalReglements = $reglements->sum('montant');
        $montantDu = (float) $facture->montant_net;
        $solde = $montantDu - (float) $totalReglements;

        return response()->json([
            'success' => true,
            'etablissement' => \App\Models\Setting::getEtablissement(),
            'facture' => [
                'id' => $facture->id,
                'numero_piece' => $facture->numero_piece,
                'date' => $facture->date?->format('d/m/Y'),
                'reference_facture' => $facture->reference_facture,
                'libelle' => $facture->libelle,
                'montant_facture' => number_format((float) $facture->montant_facture, 0, ',', ' '),
                'montant_mo' => number_format((float) $facture->montant_mo, 0, ',', ' '),
                'avoir' => number_format((float) $facture->avoir, 0, ',', ' '),
                'assujetti_tva' => (bool) $facture->assujetti_tva,
                'taux_tva' => $facture->taux_tva ? (float) $facture->taux_tva : 0,
                'montant_tva' => number_format((float) ($facture->montant_tva ?? 0), 0, ',', ' '),
                'montant_ttc' => number_format((float) ($facture->montant_ttc ?? 0), 0, ',', ' '),
                'taux_aib' => (float) $facture->taux,
                'montant_aib' => number_format((float) ($facture->montant_reduction ?? 0), 0, ',', ' '),
            ],
            'fournisseur' => [
                'nom' => $facture->fournisseur_nom ?: $facture->fournisseur?->nom,
                'code' => $facture->fournisseur?->compteComptable?->numero_compte,
            ],
            'reglements' => $reglements->values()->map(fn($r, $index) => [
                'numero_ordre' => $r->date_reglement?->format('dmY') . '-' . $r->created_at?->format('Hi'),
                'date_reglement' => $r->date_reglement?->format('d/m/Y'),
                'mode_paiement' => match($r->mode_paiement) {
                    'cheque' => 'Chèque',
                    'virement' => 'Virement Bancaire',
                    'especes' => 'Espèces',
                    default => ucfirst($r->mode_paiement ?? ''),
                },
                'beneficiaire' => $r->beneficiaire ?: ($facture->fournisseur_nom ?: $facture->fournisseur?->nom),
                'montant' => number_format((float) $r->montant, 0, ',', ' '),
            ])->toArray(),
            'total_reglements' => number_format((float) $totalReglements, 0, ',', ' '),
            'montant_du' => number_format($montantDu, 0, ',', ' '),
            'solde' => number_format($solde, 0, ',', ' '),
        ]);
    }

    /**
     * PDF "État de Règlement Facture"
     */
    public function etatReglementPdf(int $id)
    {
        $facture = FactureFournisseur::with(['fournisseur.compteComptable'])
            ->findOrFail($id);

        $reglements = ReglementFournisseur::where('facture_id', $id)
            ->where('statut', '!=', ReglementFournisseur::STATUT_ANNULE)
            ->orderBy('date_reglement')
            ->orderBy('id')
            ->get();

        $totalReglements = $reglements->sum('montant');
        $montantDu = (float) $facture->montant_net;
        $solde = $montantDu - (float) $totalReglements;

        $fournisseurNom = $facture->fournisseur_nom ?: $facture->fournisseur?->nom;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.etat-reglement-facture', [
            'facture' => $facture,
            'fournisseur' => $facture->fournisseur,
            'fournisseurNom' => $fournisseurNom,
            'reglements' => $reglements,
            'totalReglements' => $totalReglements,
            'montantDu' => $montantDu,
            'solde' => $solde,
            'user' => auth()->user(),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);

        return $pdf->stream("etat-reglement-{$facture->id}.pdf");
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
            'imputations' => ['nullable', 'array'],
            'imputations.*.compte_id' => ['required_with:imputations', 'integer', 'exists:plan_comptable_ohada,id'],
            'imputations.*.nature' => ['required_with:imputations', 'string', 'in:debit,credit'],
            'imputations.*.montant' => ['required_with:imputations', 'numeric', 'min:0'],
            'imputations.*.libelle' => ['nullable', 'string', 'max:500'],
            'imputations.*.imputation_code' => ['nullable', 'string', 'max:10'],
        ];
    }

    /**
     * Liste centralisée des imputations possibles + comptes filtrés
     * pour le formulaire de facture fournisseur.
     *
     * Imputations : Classe 2 (Immo), 42 (Personnel), 6 (Charges), 401 (Fournisseurs), 445 (TVA).
     * Comptes : tous les comptes de ces préfixes, en excluant les comptes auxiliaires
     * auto-créés pour la classe 401 (is_custom=true).
     */
    private function getImputationsAndComptes(): array
    {
        $imputations = Classe::imputationsFactureFournisseur()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->code,
                'numero' => $c->code,
                'libelle' => $c->libelle,
                'prefixe_compte' => $c->prefixe_compte,
                'classe' => $c->code,
                // Nature comptable : debit (charges/immo/personnel) ou credit (fournisseurs)
                'nature' => in_array($c->code, ['401', '481']) ? 'credit' : 'debit',
            ]);

        // Comptes : 2*, 42*, 6*, 401*, 481* (sans les comptes auxiliaires custom).
        // 445* est inclus pour la génération automatique des écritures TVA.
        $prefixes = ['2', '42', '6', '401', '481', '445'];

        $comptes = CompteComptable::where(function ($q) use ($prefixes) {
                foreach ($prefixes as $p) {
                    $q->orWhere('numero_compte', 'LIKE', $p . '%');
                }
            })
            ->whereRaw('LENGTH(numero_compte) >= 2')
            // Pour les comptes 401/481 : exclure les comptes auxiliaires auto-créés
            ->where(function ($q) {
                $q->where('numero_compte', 'NOT LIKE', '401%')
                  ->where('numero_compte', 'NOT LIKE', '481%')
                  ->orWhere('is_custom', false);
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'code' => $c->numero_compte,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
                'classe' => substr($c->numero_compte, 0, 1),
            ]);

        return ['imputations' => $imputations, 'comptes' => $comptes];
    }

    /**
     * Synchronise les imputations multiples d'une facture.
     * Si aucune ligne n'est envoyée, crée une ligne unique à partir de compte_id.
     */
    private function syncImputationsMultiples(FactureFournisseur $facture, array $lignes): void
    {
        $facture->imputations()->delete();

        if (empty($lignes)) {
            if ($facture->compte_id) {
                // Legacy : créer une ligne débit à partir du compte unique
                $facture->imputations()->create([
                    'compte_id' => $facture->compte_id,
                    'nature' => 'debit',
                    'montant' => (float) ($facture->montant_ttc ?: $facture->montant_facture),
                    'libelle' => $facture->libelle,
                ]);
            }
            return;
        }

        foreach ($lignes as $ligne) {
            if (empty($ligne['compte_id'])) continue;
            $facture->imputations()->create([
                'compte_id' => (int) $ligne['compte_id'],
                'nature' => $ligne['nature'] ?? $this->deduireNatureFromCompte((int) $ligne['compte_id']),
                'montant' => (float) ($ligne['montant'] ?? 0),
                'libelle' => $ligne['libelle'] ?? $facture->libelle,
            ]);
        }
    }

    /**
     * Déduit la nature (debit/credit) à partir du numéro du compte
     * Fallback si la nature n'est pas envoyée explicitement.
     */
    private function deduireNatureFromCompte(int $compteId): string
    {
        $compte = CompteComptable::find($compteId);
        if (!$compte) return 'debit';
        $num = $compte->numero_compte;
        return (str_starts_with($num, '401') || str_starts_with($num, '481')) ? 'credit' : 'debit';
    }
}
