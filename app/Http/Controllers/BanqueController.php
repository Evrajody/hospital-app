<?php

namespace App\Http\Controllers;

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
            ->orderBy('banque_id')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->banque->nom . ' - ' . $c->numero_compte,
                'banque' => $c->banque->nom,
                'numero' => $c->numero_compte,
                'compte_ohada' => $c->compteOhada ? $c->compteOhada->numero_compte . ' - ' . $c->compteOhada->libelle : null,
                'solde' => (float) $c->solde,
                'observations' => $c->observations,
                'type' => 'banque',
            ]);

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
            ->where('utilisable', true)
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
     * Supprimer une banque (API)
     */
    public function destroyBanque(Banque $banque): JsonResponse
    {
        // Vérifier si la banque a des comptes
        if ($banque->comptes()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette banque car elle possède des comptes bancaires.',
            ], 422);
        }

        $banque->delete();

        return response()->json([
            'success' => true,
            'message' => 'Banque supprimée avec succès',
        ]);
    }
}
