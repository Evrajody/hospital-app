<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Banque;
use App\Models\CompteBancaire;
use App\Models\ApprovisionnementBanque;
use App\Models\CompteComptable;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class BanqueController extends Controller
{
    /**
     * Afficher la liste des banques et comptes (Vue Inertia)
     */
    public function index(): InertiaResponse
    {
        $comptes = CompteBancaire::with(['banque', 'compteOhada'])
            ->withCount(['approvisionnements as nb_entrees'])
            ->orderBy('banque_id')
            ->get()
            ->map(function ($c) {
                // Compter les sorties (règlements fournisseurs liés à ce compte par numéro)
                $nbSorties = \App\Models\ReglementFournisseur::where('numero_compte_bancaire', $c->numero_compte)
                    ->where('statut', '!=', \App\Models\ReglementFournisseur::STATUT_ANNULE)
                    ->count();

                return [
                    'id' => $c->id,
                    'banque_id' => $c->banque_id,
                    'nom' => $c->banque->nom . ' - ' . $c->numero_compte,
                    'banque' => $c->banque->nom,
                    'numero' => $c->numero_compte,
                    'compte_comptable' => $c->compteOhada ? $c->compteOhada->numero_compte : null,
                    'compte_ohada_id' => $c->compte_ohada_id,
                    'compte_ohada' => $c->compteOhada ? $c->compteOhada->numero_compte . ' - ' . $c->compteOhada->libelle : null,
                    'solde' => (float) $c->solde,
                    'observations' => $c->observations,
                    'type' => 'banque',
                    'mouvements' => [
                        'entrees' => $c->nb_entrees,
                        'sorties' => $nbSorties,
                    ],
                ];
            });

        $banques = Banque::withCount('comptes')
            ->orderBy('nom')
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'nom' => $b->nom,
                'comptes_count' => $b->comptes_count,
            ]);

        // Comptes OHADA de trésorerie (classe 5)
        $comptesOhada = CompteComptable::where('numero_compte', 'LIKE', '5%')
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
            ]);

        // Statistiques
        $stats = [
            'solde_total' => CompteBancaire::sum('solde'),
            'entrees_mois' => ApprovisionnementBanque::whereMonth('date_depot', now()->month)
                ->whereYear('date_depot', now()->year)
                ->sum('montant'),
            'sorties_mois' => 0, // À implémenter avec les décaissements
        ];

        return Inertia::render('Banques/Index', [
            'banques' => $comptes, // Pour la compatibilité avec le frontend existant
            'listeBanques' => $banques,
            'comptesOhada' => $comptesOhada,
            'stats' => $stats,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? '',
            ],
        ]);
    }

    /**
     * Créer une nouvelle banque (API)
     */
    public function storeBanque(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'nom' => 'required|string|max:100|unique:banques,nom',
        ]);

        $banque = Banque::create($validated);

        ActivityLog::log('create', 'banque', "Création de la banque {$banque->nom}", $banque);

        return response()->json([
            'success' => true,
            'message' => 'Banque créée avec succès',
            'data' => [
                'id' => $banque->id,
                'nom' => $banque->nom,
            ],
        ]);
    }

    /**
     * Créer un nouveau compte bancaire (API)
     */
    public function storeCompte(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'banque_id' => 'required|exists:banques,id',
            'numero_compte' => 'required|string|max:50',
            'compte_ohada_id' => 'required|exists:plan_comptable_ohada,id',
            'observations' => 'nullable|string',
        ]);

        $compte = CompteBancaire::create($validated);
        $compte->load(['banque', 'compteOhada']);

        ActivityLog::log('create', 'compte_bancaire', "Création du compte bancaire {$compte->banque->nom} - {$compte->numero_compte}", $compte);

        return response()->json([
            'success' => true,
            'message' => 'Compte bancaire créé avec succès',
            'data' => [
                'id' => $compte->id,
                'nom' => $compte->banque->nom . ' - ' . $compte->numero_compte,
                'banque' => $compte->banque->nom,
                'numero' => $compte->numero_compte,
                'compte_ohada' => $compte->compteOhada ? $compte->compteOhada->numero_compte : null,
                'solde' => (float) $compte->solde,
            ],
        ]);
    }

    /**
     * Enregistrer un approvisionnement (API)
     */
    public function storeApprovisionnement(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'compte_bancaire_id' => 'required|exists:comptes_bancaires,id',
            'date_depot' => 'required|date',
            'montant' => 'required|numeric|min:1',
            'observations' => 'nullable|string',
            'piece_jointe' => 'nullable|file|mimes:pdf,jpg,jpeg,png|max:5120',
        ]);

        try {
            DB::beginTransaction();

            $compte = CompteBancaire::findOrFail($validated['compte_bancaire_id']);

            // Upload pièce jointe si présente
            $pieceJointe = null;
            if ($request->hasFile('piece_jointe')) {
                $pieceJointe = $request->file('piece_jointe')->store('approvisionnements', 'public');
            }

            $approvisionnement = $compte->approvisionner(
                $validated['montant'],
                $validated['date_depot'],
                $validated['observations'] ?? null,
                $pieceJointe
            );

            DB::commit();

            $montant = (float) $validated['montant'];
            ActivityLog::log('create', 'compte_bancaire', "Approvisionnement de " . number_format($montant, 0, ',', ' ') . " XOF sur {$compte->banque->nom} - {$compte->numero_compte}", $compte, ['montant' => $montant]);

            return response()->json([
                'success' => true,
                'message' => 'Approvisionnement enregistré avec succès',
                'data' => [
                    'id' => $approvisionnement->id,
                    'nouveau_solde' => (float) $compte->fresh()->solde,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste des banques pour select (API)
     */
    public function listeBanques(): JsonResponse
    {
        $banques = Banque::orderBy('nom')->get()->map(fn($b) => [
            'id' => $b->id,
            'nom' => $b->nom,
        ]);

        return response()->json([
            'success' => true,
            'data' => $banques,
        ]);
    }

    /**
     * Liste des comptes bancaires pour select (API)
     */
    public function listeComptes(): JsonResponse
    {
        $comptes = CompteBancaire::with('banque')
            ->orderBy('banque_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->banque->nom . ' - ' . $c->numero_compte,
                'banque_id' => $c->banque_id,
                'banque' => $c->banque->nom,
                'numero' => $c->numero_compte,
                'solde' => (float) $c->solde,
            ]);

        return response()->json([
            'success' => true,
            'data' => $comptes,
        ]);
    }

    /**
     * Afficher les mouvements d'un compte bancaire (Vue Inertia)
     */
    public function mouvements(int $id, Request $request): InertiaResponse
    {
        $compte = CompteBancaire::with('banque')->findOrFail($id);

        // Approvisionnements (entrées)
        $entrees = ApprovisionnementBanque::where('compte_bancaire_id', $id)
            ->with('createur')
            ->get()
            ->map(fn($a) => [
                'id' => 'appro-' . $a->id,
                'date' => $a->date_depot->format('Y-m-d'),
                'type' => 'entree',
                'reference' => null,
                'description' => $a->observations ?: 'Approvisionnement',
                'montant' => (float) $a->montant,
                'origine' => 'approvisionnement',
                'user' => $a->createur ? ['name' => $a->createur->name] : null,
                'created_at' => $a->created_at,
            ]);

        // Règlements fournisseurs (sorties) — ceux liés à ce compte
        $sorties = \App\Models\ReglementFournisseur::where('numero_compte_bancaire', $compte->numero_compte)
            ->where('statut', '!=', \App\Models\ReglementFournisseur::STATUT_ANNULE)
            ->with(['fournisseur', 'createur'])
            ->get()
            ->map(fn($r) => [
                'id' => 'reg-' . $r->id,
                'date' => $r->date_reglement?->format('Y-m-d'),
                'type' => 'sortie',
                'reference' => $r->reference,
                'description' => 'Règlement ' . ($r->fournisseur?->nom ?? ''),
                'montant' => (float) $r->montant,
                'origine' => 'reglement_fournisseur',
                'user' => $r->createur ? ['name' => $r->createur->name] : null,
                'created_at' => $r->created_at,
            ]);

        // Fusionner et trier par date desc
        $mouvements = $entrees->concat($sorties)
            ->sortByDesc('date')
            ->values();

        // Calculer solde_apres pour chaque mouvement
        $soldeActuel = (float) $compte->solde;
        $mouvementsArray = $mouvements->toArray();

        // Parcourir depuis le plus récent, le solde après le dernier mouvement = solde actuel
        $solde = $soldeActuel;
        for ($i = 0; $i < count($mouvementsArray); $i++) {
            $mouvementsArray[$i]['solde_apres'] = $solde;
            if ($mouvementsArray[$i]['type'] === 'entree') {
                $solde -= $mouvementsArray[$i]['montant'];
            } else {
                $solde += $mouvementsArray[$i]['montant'];
            }
        }

        // Pagination manuelle
        $perPage = $request->input('per_page', 20);
        $page = $request->input('page', 1);
        $total = count($mouvementsArray);
        $paginatedItems = array_slice($mouvementsArray, ($page - 1) * $perPage, $perPage);

        return Inertia::render('Banques/Mouvements', [
            'banque' => [
                'id' => $compte->id,
                'nom' => $compte->banque->nom . ' - ' . $compte->numero_compte,
                'numero' => $compte->numero_compte,
                'banque_nom' => $compte->banque->nom,
                'solde' => (float) $compte->solde,
                'type' => 'banque',
                'compte_comptable' => $compte->compteOhada?->numero_compte,
            ],
            'mouvements' => $paginatedItems,
            'stats' => [
                'total_entrees' => $entrees->sum('montant'),
                'total_sorties' => $sorties->sum('montant'),
                'nombre_mouvements' => $total,
            ],
            'pagination' => [
                'current_page' => (int) $page,
                'per_page' => (int) $perPage,
                'total' => $total,
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? '',
            ],
        ]);
    }

    /**
     * Modifier un compte bancaire (API)
     */
    public function updateCompte(Request $request, CompteBancaire $compte): JsonResponse
    {
        $validated = $request->validate([
            'numero_compte' => 'required|string|max:50',
            'compte_ohada_id' => 'required|exists:plan_comptable_ohada,id',
            'observations' => 'nullable|string',
        ]);

        $compte->update($validated);
        $compte->load(['banque', 'compteOhada']);

        ActivityLog::log('update', 'compte_bancaire', "Modification du compte bancaire {$compte->banque->nom} - {$compte->numero_compte}", $compte);

        return response()->json([
            'success' => true,
            'message' => 'Compte bancaire modifié avec succès',
        ]);
    }

    /**
     * Supprimer une banque (API)
     */
    public function destroyBanque(Banque $banque): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Supprimer les approvisionnements des comptes de cette banque
            $comptes = $banque->comptes;
            foreach ($comptes as $compte) {
                ApprovisionnementBanque::where('compte_bancaire_id', $compte->id)->delete();
            }

            // Supprimer les comptes bancaires de cette banque
            $banque->comptes()->delete();

            // Supprimer la banque
            $nom = $banque->nom;
            $banque->delete();

            DB::commit();

            ActivityLog::log('delete', 'banque', "Suppression de la banque {$nom}", null, ['nom' => $nom]);

            return response()->json([
                'success' => true,
                'message' => 'Banque et ses comptes supprimés avec succès',
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression de la banque',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
