<?php

namespace App\Http\Controllers;

use App\Models\Banque;
use App\Models\Client;
use App\Models\FactureClient;
use App\Models\ReglementClient;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class ReglementClientController extends Controller
{
    /**
     * Liste des règlements clients
     */
    public function indexView(Request $request): InertiaResponse
    {
        $query = ReglementClient::with(['facture', 'client', 'banqueDepot', 'compteBancaire'])
            ->orderBy('date_reglement', 'desc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_ligne', 'LIKE', "%{$search}%")
                  ->orWhere('reference_cheque', 'LIKE', "%{$search}%")
                  ->orWhere('institution', 'LIKE', "%{$search}%")
                  ->orWhereHas('facture', fn($fq) => $fq->where('reference', 'LIKE', "%{$search}%"))
                  ->orWhereHas('client', fn($cq) => $cq->where('nom', 'LIKE', "%{$search}%"));
            });
        }

        $reglements = $query->get()->map(fn($r) => $r->toApiArray());

        $clients = Client::orderBy('nom')->get()->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
        ]);

        $facturesImpayees = FactureClient::with('client')
            ->whereIn('statut', [FactureClient::STATUT_NON_PAYEE, FactureClient::STATUT_PARTIELLEMENT_PAYEE])
            ->orderBy('date_facture', 'desc')
            ->get()
            ->map(fn($f) => [
                'id' => $f->id,
                'reference' => $f->reference,
                'client_nom' => $f->client?->nom,
                'montant' => (float) $f->montant,
                'reste_a_payer' => (float) $f->reste_a_payer,
            ]);

        $stats = [
            'total_reglements' => (float) ReglementClient::sum('montant'),
            'reglements_mois' => (float) ReglementClient::whereMonth('date_reglement', now()->month)
                ->whereYear('date_reglement', now()->year)
                ->sum('montant'),
            'nombre_reglements' => ReglementClient::count(),
            'montant_moyen' => (float) (ReglementClient::avg('montant') ?? 0),
        ];

        return Inertia::render('ReglementClients/Index', [
            'reglements' => $reglements,
            'clients' => $clients,
            'facturesImpayees' => $facturesImpayees,
            'stats' => $stats,
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    /**
     * Créer un règlement client
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'facture_id' => ['required', 'integer', 'exists:factures_clients,id'],
            'date_reglement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'numero_ligne' => ['nullable', 'string', 'max:50'],
            'institution' => ['nullable', 'string', 'max:255'],
            'reference_cheque' => ['nullable', 'string', 'max:100'],
            'banque_depot_id' => ['nullable', 'integer', 'exists:banques,id'],
            'compte_bancaire_id' => ['nullable', 'integer', 'exists:comptes_bancaires,id'],
            'observations' => ['nullable', 'string'],
        ]);

        $facture = FactureClient::findOrFail($request->facture_id);

        // Vérifier que le montant ne dépasse pas le reste à payer
        if ($request->montant > (float) $facture->reste_a_payer) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant dépasse le reste à payer (' . number_format($facture->reste_a_payer, 0, ',', ' ') . ' XOF)',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reglement = ReglementClient::create([
                'numero_ligne' => $request->numero_ligne,
                'date_reglement' => $request->date_reglement,
                'facture_id' => $facture->id,
                'client_id' => $facture->client_id,
                'montant' => $request->montant,
                'institution' => $request->institution,
                'reference_cheque' => $request->reference_cheque,
                'banque_depot_id' => $request->banque_depot_id,
                'compte_bancaire_id' => $request->compte_bancaire_id,
                'observations' => $request->observations,
                'created_by' => auth()->id(),
            ]);

            $facture->enregistrerPaiement($request->montant);

            DB::commit();

            $reglement->load(['facture', 'client', 'banqueDepot', 'compteBancaire']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement enregistré avec succès',
                'data' => $reglement->toApiArray(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de l\'enregistrement : ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Supprimer un règlement
     */
    public function destroy(int $id): JsonResponse
    {
        $reglement = ReglementClient::findOrFail($id);
        $facture = $reglement->facture;

        try {
            DB::beginTransaction();

            $montant = (float) $reglement->montant;
            $reglement->delete();

            // Reverser le paiement sur la facture
            $facture->montant_paye = max(0, (float) $facture->montant_paye - $montant);
            $facture->reste_a_payer = (float) $facture->montant - (float) $facture->montant_paye;

            if ($facture->montant_paye <= 0) {
                $facture->statut = FactureClient::STATUT_NON_PAYEE;
            } elseif ($facture->reste_a_payer <= 0.01) {
                $facture->statut = FactureClient::STATUT_PAYEE;
                $facture->reste_a_payer = 0;
            } else {
                $facture->statut = FactureClient::STATUT_PARTIELLEMENT_PAYEE;
            }

            $facture->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Règlement supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la suppression',
            ], 500);
        }
    }
}
