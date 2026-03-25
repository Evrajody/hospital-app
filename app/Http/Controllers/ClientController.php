<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\Client;
use App\Models\CompteComptable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ClientController extends Controller
{
    /**
     * Afficher la liste des clients (Vue Inertia)
     */
    public function index(Request $request): InertiaResponse
    {
        $query = Client::with('compteComptable');

        // Recherche
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('nom', 'ILIKE', "%{$search}%")
                  ->orWhere('telephone', 'ILIKE', "%{$search}%")
                  ->orWhereHas('compteComptable', function ($cq) use ($search) {
                      $cq->where('numero_compte', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Tri
        $sort = $request->input('sort', 'nom');
        $order = $request->input('order', 'asc') === 'asc' ? 'asc' : 'desc';
        $allowedSorts = ['nom', 'telephone', 'adresse'];

        if ($sort === 'code') {
            $query->leftJoin('plan_comptable_ohada', 'clients.compte_comptable_id', '=', 'plan_comptable_ohada.id')
                  ->orderBy('plan_comptable_ohada.numero_compte', $order)
                  ->select('clients.*');
        } elseif (in_array($sort, $allowedSorts)) {
            $query->orderBy($sort, $order);
        } else {
            $query->orderBy('nom', $order);
        }

        // Pagination
        $perPage = $request->input('per_page', 20);
        $clientsPaginated = $query->paginate($perPage);

        $clients = $clientsPaginated->getCollection()->map(function ($client) {
            $data = $client->toApiArray();
            $data['code'] = $client->compteComptable?->numero_compte ?? '-';
            return $data;
        });

        // Comptes clients (41%, 424100%) pour le select
        $comptesClients = CompteComptable::where(function ($q) {
                $q->where('numero_compte', 'LIKE', '41%')
                  ->orWhere('numero_compte', 'LIKE', '424100%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
            ]);

        // Comptes parents pour la création de nouveaux comptes (tous les comptes clients)
        $comptesParents = CompteComptable::where(function ($q) {
                $q->where('numero_compte', 'LIKE', '41%')
                  ->orWhere('numero_compte', 'LIKE', '424100%');
            })
            ->orderBy('numero_compte')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'numero' => $c->numero_compte,
                'libelle' => $c->libelle,
            ]);

        return Inertia::render('Clients/Index', [
            'clients' => $clients,
            'comptesClients' => $comptesClients,
            'comptesParents' => $comptesParents,
            'pagination' => [
                'current_page' => $clientsPaginated->currentPage(),
                'per_page' => $clientsPaginated->perPage(),
                'total' => $clientsPaginated->total(),
                'last_page' => $clientsPaginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
            ],
            'stats' => [
                'total' => Client::count(),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Créer un nouveau client (API)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'adresse' => ['nullable', 'string'],
            'compte_comptable_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'create_compte' => ['nullable', 'boolean'],
            'compte_parent_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'nouveau_compte_numero' => ['nullable', 'string', 'regex:/^(41|4111|424100)[a-zA-Z0-9.]+$/'],
            'nouveau_compte_libelle' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            $compteComptableId = $request->compte_comptable_id;

            // Créer un nouveau compte si demandé
            if ($request->create_compte && $request->nouveau_compte_numero) {
                // Vérifier que le compte n'existe pas déjà
                if (CompteComptable::where('numero_compte', $request->nouveau_compte_numero)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce numéro de compte existe déjà',
                        'errors' => ['nouveau_compte_numero' => ['Ce numéro de compte existe déjà dans le plan comptable']],
                    ], 422);
                }

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

            $client = Client::create([
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'compte_comptable_id' => $compteComptableId,
            ]);

            $client->load('compteComptable');

            DB::commit();

            ActivityLog::log('create', 'client', "Création du client {$client->nom}", $client, ['nom' => $client->nom]);

            return response()->json([
                'success' => true,
                'message' => 'Client créé avec succès',
                'data' => $client->toApiArray(),
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la création du client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Modifier un client (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $client = Client::findOrFail($id);

        $request->validate([
            'nom' => ['required', 'string', 'min:2', 'max:255'],
            'telephone' => ['nullable', 'string', 'max:30'],
            'adresse' => ['nullable', 'string'],
            'compte_comptable_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'create_compte' => ['nullable', 'boolean'],
            'compte_parent_id' => ['nullable', 'integer', 'exists:plan_comptable_ohada,id'],
            'nouveau_compte_numero' => ['nullable', 'string', 'regex:/^(41|4111|424100)[a-zA-Z0-9.]+$/'],
            'nouveau_compte_libelle' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            DB::beginTransaction();

            $compteComptableId = $request->compte_comptable_id;

            if ($request->create_compte && $request->nouveau_compte_numero) {
                if (CompteComptable::where('numero_compte', $request->nouveau_compte_numero)->exists()) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Ce numéro de compte existe déjà',
                        'errors' => ['nouveau_compte_numero' => ['Ce numéro de compte existe déjà dans le plan comptable']],
                    ], 422);
                }

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

            $client->update([
                'nom' => $request->nom,
                'telephone' => $request->telephone,
                'adresse' => $request->adresse,
                'compte_comptable_id' => $compteComptableId,
            ]);

            $client->load('compteComptable');

            DB::commit();

            ActivityLog::log('update', 'client', "Modification du client {$client->nom}", $client, ['nom' => $client->nom]);

            return response()->json([
                'success' => true,
                'message' => 'Client modifié avec succès',
                'data' => $client->toApiArray(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification du client',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un client (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $client = Client::findOrFail($id);
        $nom = $client->nom;
        $client->delete();

        ActivityLog::log('delete', 'client', "Suppression du client {$nom}", null, ['nom' => $nom]);

        return response()->json([
            'success' => true,
            'message' => 'Client supprimé avec succès',
        ]);
    }
}
