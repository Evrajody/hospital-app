<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Client;
use App\Models\FactureClient;
use App\Models\ReglementClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FactureClientController extends Controller
{
    /**
     * Afficher la liste des factures clients
     */
    public function indexView(): InertiaResponse
    {
        $factures = FactureClient::with('client')
            ->orderBy('date_facture', 'desc')
            ->get()
            ->map(fn($f) => $f->toApiArray());

        $clients = Client::orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'code' => $c->compteComptable?->numero_compte ?? '-',
            ]);

        $prochaineReference = FactureClient::genererReference();

        return Inertia::render('Clients/Factures/Index', [
            'factures' => $factures,
            'clients' => $clients,
            'prochaineReference' => $prochaineReference,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Afficher le détail d'une facture client
     */
    public function showView(int $id): InertiaResponse
    {
        $facture = FactureClient::with('client')->findOrFail($id);

        $clients = Client::orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'code' => $c->compteComptable?->numero_compte ?? '-',
            ]);

        $prochaineReference = FactureClient::genererReference();

        return Inertia::render('Clients/Factures/Show', [
            'facture' => $facture->toApiArray(),
            'clients' => $clients,
            'prochaineReference' => $prochaineReference,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Page de règlement d'une facture client
     */
    public function reglementView(int $id): InertiaResponse
    {
        $facture = FactureClient::with('client')->findOrFail($id);

        $reglements = ReglementClient::with(['banqueDepot', 'compteBancaire'])
            ->where('facture_id', $id)
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        $banques = Banque::with(['comptes' => function ($q) {
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
                ]),
            ]);

        $institutions = ReglementClient::getInstitutions();

        return Inertia::render('Clients/Factures/Reglement', [
            'facture' => $facture->toApiArray(),
            'reglements' => $reglements,
            'banques' => $banques,
            'institutions' => $institutions,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Créer une facture client (API)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'reference' => ['required', 'string', 'max:20', 'unique:factures_clients,reference'],
            'date_facture' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'ristourne' => ['nullable', 'numeric', 'min:0'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        // Vérifier s'il y a un saut de numéro
        $saut = FactureClient::verifierSaut($request->reference);
        if ($saut !== null && !$request->force_saut) {
            return response()->json([
                'success' => false,
                'saut_numero' => true,
                'message' => "Attention : vous sautez {$saut} numéro(s) de référence.",
                'numeros_sautes' => $saut,
            ], 422);
        }

        $ristourne = $request->ristourne ?? 0;

        $facture = FactureClient::create([
            'reference' => $request->reference,
            'date_facture' => $request->date_facture,
            'montant' => $request->montant,
            'ristourne' => $ristourne,
            'client_id' => $request->client_id,
            'reste_a_payer' => $request->montant - $ristourne,
            'statut' => FactureClient::STATUT_NON_PAYEE,
            'created_by' => auth()->id(),
        ]);

        $facture->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Facture client créée avec succès',
            'data' => $facture->toApiArray(),
        ], 201);
    }

    /**
     * Modifier une facture client (API)
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $facture = FactureClient::findOrFail($id);

        $request->validate([
            'reference' => ['required', 'string', 'max:20', 'unique:factures_clients,reference,' . $id],
            'date_facture' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'ristourne' => ['nullable', 'numeric', 'min:0'],
            'client_id' => ['required', 'integer', 'exists:clients,id'],
        ]);

        $facture->update([
            'reference' => $request->reference,
            'date_facture' => $request->date_facture,
            'montant' => $request->montant,
            'ristourne' => $request->ristourne ?? 0,
            'client_id' => $request->client_id,
        ]);

        $facture->load('client');

        return response()->json([
            'success' => true,
            'message' => 'Facture modifiée avec succès',
            'data' => $facture->toApiArray(),
        ]);
    }

    /**
     * Supprimer une facture client (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $facture = FactureClient::findOrFail($id);

        if ((float) $facture->montant_paye > 0) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer une facture ayant des règlements',
            ], 422);
        }

        $facture->delete();

        return response()->json([
            'success' => true,
            'message' => 'Facture supprimée avec succès',
        ]);
    }
}
