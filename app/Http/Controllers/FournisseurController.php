<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Fournisseur;
use App\Models\FactureFournisseur;
use App\Models\ReglementFournisseur;
use App\Models\CompteComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class FournisseurController extends Controller
{
    /**
     * Afficher la liste des fournisseurs
     */
    public function index(Request $request): Response
    {
        $query = Fournisseur::with('compteComptable');

        // Recherche
        if ($request->filled('search')) {
            $query->recherche($request->search);
        }

        // Filtre par compte comptable
        if ($request->filled('compte_id')) {
            $query->where('compte_comptable_id', $request->compte_id);
        }

        // Filtre par type
        if ($request->filled('type')) {
            $query->where('type_fournisseur', $request->type);
        }

        // Tri
        $sortField = $request->input('sort', 'nom');
        $sortOrder = $request->input('order', 'asc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['nom', 'type_fournisseur', 'contact', 'telephone', 'email'];

        if ($sortField === 'compte_comptable') {
            $query->leftJoin('plan_comptable_ohada', 'fournisseurs.compte_comptable_id', '=', 'plan_comptable_ohada.id')
                  ->orderBy('plan_comptable_ohada.numero_compte', $sortOrder)
                  ->select('fournisseurs.*');
        } elseif (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortOrder);
        } else {
            $query->orderBy('nom', $sortOrder);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $fournisseurs = $query->paginate($perPage);

        // Transformer les données - retourner TOUTES les données pour l'édition
        $fournisseursData = $fournisseurs->through(function ($fournisseur) {
            return $fournisseur->toApiArray();
        });

        // Comptes fournisseurs pour les filtres et le formulaire (401 + 4812)
        $comptesFournisseurs = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '401%')
                  ->orWhere('numero_compte', 'LIKE', '4812%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                ];
            });

        // Comptes parents pour création de nouveaux comptes (401 + 4812)
        $comptesParents = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '401%')
                  ->orWhere('numero_compte', 'LIKE', '4812%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                ];
            });

        // Statistiques
        $stats = Fournisseur::getStatistiques();

        return Inertia::render('Fournisseurs/Index', [
            'fournisseurs' => $fournisseursData->items(),
            'comptesFournisseurs' => $comptesFournisseurs,
            'comptesParents' => $comptesParents,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $fournisseurs->currentPage(),
                'per_page' => $fournisseurs->perPage(),
                'total' => $fournisseurs->total(),
                'last_page' => $fournisseurs->lastPage(),
            ],
            'filters' => [
                'search' => $request->search,
                'compte_id' => $request->compte_id,
                'type' => $request->type,
            ],
            'user' => [
                'name' => 'Utilisateur Test',
                'email' => 'test@example.com'
            ]
        ]);
    }

    /**
     * Afficher le détail d'un fournisseur
     */
    public function show(int $id): Response
    {
        $fournisseur = Fournisseur::with('compteComptable')->findOrFail($id);

        // Charger les factures du fournisseur depuis la base de données
        $facturesQuery = FactureFournisseur::where('fournisseur_id', $id)
            ->with(['imputation', 'compte'])
            ->orderBy('date', 'desc');

        $factures = $facturesQuery->get()->map(function ($facture) {
            return [
                'id' => $facture->id,
                'numero_piece' => $facture->numero_piece,
                'numero' => $facture->numero_piece,
                'date' => $facture->date?->format('Y-m-d'),
                'date_facture' => $facture->date?->format('Y-m-d'),
                'reference_facture' => $facture->reference_facture,
                'libelle' => $facture->libelle,
                'montant_ht' => (float) $facture->montant_ht,
                'montant_tva' => (float) $facture->montant_tva,
                'montant_ttc' => (float) $facture->montant_ttc,
                'montant_net' => (float) $facture->montant_net,
                'montant_paye' => (float) $facture->montant_paye,
                'reste_a_payer' => (float) $facture->reste_a_payer,
                'statut' => $facture->statut,
                'statut_paiement' => $this->getStatutPaiement($facture),
                'date_facture_bc' => $facture->date_facture_bc?->format('Y-m-d'),
                'observations' => $facture->observations,
                'imputation_id' => $facture->imputation_id,
                'compte_id' => $facture->compte_id,
            ];
        });

        // Statistiques du fournisseur calculées à partir des factures
        $stats = [
            'nombre_factures' => $facturesQuery->count(),
            'montant_total' => (float) FactureFournisseur::where('fournisseur_id', $id)->sum('montant_net'),
            'montant_paye' => (float) FactureFournisseur::where('fournisseur_id', $id)->sum('montant_paye'),
            'montant_reste' => (float) FactureFournisseur::where('fournisseur_id', $id)->sum('reste_a_payer'),
        ];

        // Comptes fournisseurs pour le formulaire d'édition (401 + 4812)
        $comptesFournisseurs = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '401%')
                  ->orWhere('numero_compte', 'LIKE', '4812%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                ];
            });

        $comptesParents = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '401%')
                  ->orWhere('numero_compte', 'LIKE', '4812%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                ];
            });

        // Données pour le formulaire de création de factures (3 comptes principaux: 6, 2 et 42)
        $imputations = CompteComptable::whereIn('numero_compte', ['6', '2', '42'])
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'code' => $compte->numero_compte,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'classe' => substr($compte->numero_compte, 0, 1),
                ];
            });

        $comptes = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '6%')
                  ->orWhere('numero_compte', 'LIKE', '2%')
                  ->orWhere('numero_compte', 'LIKE', '42%');
            })
            ->whereRaw('LENGTH(numero_compte) >= 4')
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'code' => $compte->numero_compte,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'classe' => substr($compte->numero_compte, 0, 1),
                ];
            });

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

        // Charger les règlements du fournisseur
        $reglements = ReglementFournisseur::where('fournisseur_id', $id)
            ->with(['facture', 'createur'])
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        // Statistiques des règlements
        $statsReglements = ReglementFournisseur::getStatistiques($id);

        // Comptes de trésorerie pour le formulaire de règlement
        $comptesTresorerie = CompteComptable::where(function($q) {
                $q->where('numero_compte', 'LIKE', '52%')
                  ->orWhere('numero_compte', 'LIKE', '57%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(function ($compte) {
                return [
                    'id' => $compte->id,
                    'numero' => $compte->numero_compte,
                    'libelle' => $compte->libelle,
                    'banque' => $compte->libelle,
                ];
            });

        return Inertia::render('Fournisseurs/Show', [
            'fournisseur' => $fournisseur->toApiArray(),
            'factures' => $factures,
            'reglements' => $reglements,
            'stats' => $stats,
            'statsReglements' => $statsReglements,
            'comptesFournisseurs' => $comptesFournisseurs,
            'comptesParents' => $comptesParents,
            'imputations' => $imputations,
            'comptes' => $comptes,
            'comptesTresorerie' => $comptesTresorerie,
            'comptesAib' => $comptesAib,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ]
        ]);
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
     * Créer un nouveau fournisseur (API)
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

            // Si on doit créer un nouveau compte comptable
            $compteComptableId = $request->compte_comptable_id;

            if ($request->create_compte && $request->nouveau_compte_numero) {
                // Vérifier que le numéro n'existe pas déjà
                $existingCompte = CompteComptable::where('numero_compte', $request->nouveau_compte_numero)->first();

                if ($existingCompte) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le numéro de compte existe déjà',
                        'errors' => ['nouveau_compte_numero' => ['Ce numéro de compte existe déjà']],
                    ], 422);
                }

                // Déterminer le parent via compte_parent_id
                $parent = $request->compte_parent_id
                    ? CompteComptable::find($request->compte_parent_id)
                    : null;

                $niveau = $parent ? $parent->niveau + 1 : 5;

                // Créer le nouveau compte
                $nouveauCompte = CompteComptable::create([
                    'numero_compte' => $request->nouveau_compte_numero,
                    'libelle' => $request->nouveau_compte_libelle ?: $request->nom,
                    'classe' => 4, // Classe Tiers
                    'niveau' => $niveau,
                    'parent_id' => $parent?->id,
                ]);

                $compteComptableId = $nouveauCompte->id;
            }

            // Créer le fournisseur
            $fournisseur = Fournisseur::create([
                'nom' => $request->nom,
                'type_fournisseur' => $request->type_fournisseur,
                'contact' => $request->contact,
                'fonction_contact' => $request->fonction_contact,
                'telephone' => $request->telephone,
                'telephone_secondaire' => $request->telephone_secondaire,
                'email' => $request->email,
                'site_web' => $request->site_web,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'pays' => $request->pays ?? 'BJ',
                'compte_comptable_id' => $compteComptableId,
                'ifu' => $request->ifu,
                'rccm' => $request->rccm,
                'observations' => $request->observations,
            ]);

            $this->syncFournisseurComptes($fournisseur, $compteComptableId, $request->input('comptes_supplementaires', []));

            DB::commit();

            ActivityLog::log('create', 'fournisseur', "Création du fournisseur {$fournisseur->nom}", $fournisseur, ['nom' => $fournisseur->nom]);

            // Recharger avec la relation
            $fournisseur->load('compteComptable');

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur créé avec succès',
                'data' => $fournisseur->toApiArray(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du fournisseur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Mettre à jour un fournisseur (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);

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

            // Gérer le compte comptable
            $compteComptableId = $request->compte_comptable_id ?? $fournisseur->compte_comptable_id;

            if ($request->create_compte && $request->nouveau_compte_numero) {
                $existingCompte = CompteComptable::where('numero_compte', $request->nouveau_compte_numero)->first();

                if ($existingCompte) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le numéro de compte existe déjà',
                        'errors' => ['nouveau_compte_numero' => ['Ce numéro de compte existe déjà']],
                    ], 422);
                }

                // Déterminer le parent via compte_parent_id
                $parent = $request->compte_parent_id
                    ? CompteComptable::find($request->compte_parent_id)
                    : null;

                $niveau = $parent ? $parent->niveau + 1 : 5;

                $nouveauCompte = CompteComptable::create([
                    'numero_compte' => $request->nouveau_compte_numero,
                    'libelle' => $request->nouveau_compte_libelle ?: $request->nom,
                    'classe' => 4,
                    'niveau' => $niveau,
                    'parent_id' => $parent?->id,
                ]);

                $compteComptableId = $nouveauCompte->id;
            }

            // Mettre à jour le fournisseur
            $fournisseur->update([
                'nom' => $request->nom ?? $fournisseur->nom,
                'type_fournisseur' => $request->type_fournisseur,
                'contact' => $request->contact,
                'fonction_contact' => $request->fonction_contact,
                'telephone' => $request->telephone,
                'telephone_secondaire' => $request->telephone_secondaire,
                'email' => $request->email,
                'site_web' => $request->site_web,
                'adresse' => $request->adresse,
                'ville' => $request->ville,
                'pays' => $request->pays ?? $fournisseur->pays,
                'compte_comptable_id' => $compteComptableId,
                'ifu' => $request->ifu,
                'rccm' => $request->rccm,
                'observations' => $request->observations,
            ]);

            if ($request->has('comptes_supplementaires') || $request->has('compte_comptable_id')) {
                $this->syncFournisseurComptes($fournisseur, $compteComptableId, $request->input('comptes_supplementaires', []));
            }

            DB::commit();

            ActivityLog::log('update', 'fournisseur', "Modification du fournisseur {$fournisseur->nom}", $fournisseur, ['nom' => $fournisseur->nom]);

            $fournisseur->load('compteComptable');

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur modifié avec succès',
                'data' => $fournisseur->toApiArray(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du fournisseur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un fournisseur (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $fournisseur = Fournisseur::findOrFail($id);

        // Vérifier si le fournisseur a des factures
        $nbFactures = FactureFournisseur::where('fournisseur_id', $id)->count();
        if ($nbFactures > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer ce fournisseur car il est lié à {$nbFactures} facture(s). Veuillez d'abord supprimer les factures associées.",
            ], 422);
        }

        try {
            $nom = $fournisseur->nom;
            $fournisseur->delete();

            ActivityLog::log('delete', 'fournisseur', "Suppression du fournisseur {$nom}", null, ['nom' => $nom]);

            return response()->json([
                'success' => true,
                'message' => 'Fournisseur supprimé avec succès',
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression du fournisseur',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Obtenir les statistiques des fournisseurs (API)
     */
    public function stats(): JsonResponse
    {
        $stats = Fournisseur::getStatistiques();

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
        return [
            'nom' => ['required', 'string', 'min:2', 'max:255'],
            'type_fournisseur' => ['nullable', 'string', Rule::in([
                'medicaments', 'equipements', 'consommables', 'services', 'maintenance', 'autres'
            ])],
            'contact' => ['nullable', 'string', 'max:255'],
            'fonction_contact' => ['nullable', 'string', 'max:100'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'telephone_secondaire' => ['nullable', 'string', 'max:30'],
            'email' => ['nullable', 'email', 'max:255'],
            'site_web' => ['nullable', 'url', 'max:255'],
            'adresse' => ['nullable', 'string'],
            'ville' => ['nullable', 'string', 'max:100'],
            'pays' => ['nullable', 'string', 'size:2'],
            'compte_comptable_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'comptes_supplementaires' => ['nullable', 'array'],
            'comptes_supplementaires.*' => ['integer', 'exists:plan_comptable_ohada,id'],
            'ifu' => ['required', 'string', 'size:13', 'regex:/^\d{13}$/'],
            'rccm' => ['nullable', 'string', 'max:50'],
            'observations' => ['nullable', 'string'],
            'create_compte' => ['nullable', 'boolean'],
            'compte_parent_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'nouveau_compte_numero' => ['nullable', 'string', 'regex:/^(401|4812)[a-zA-Z0-9.]+$/'],
            'nouveau_compte_libelle' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Synchronise la table pivot fournisseur_comptes avec le compte principal + les supplémentaires.
     */
    private function syncFournisseurComptes(\App\Models\Fournisseur $fournisseur, ?int $principalId, array $supplementaires): void
    {
        $sync = [];
        if ($principalId) {
            $sync[(int) $principalId] = ['principal' => true];
        }
        foreach ($supplementaires as $id) {
            $id = (int) $id;
            if ($id && !isset($sync[$id])) {
                $sync[$id] = ['principal' => false];
            }
        }
        $fournisseur->comptes()->sync($sync);
    }
}
