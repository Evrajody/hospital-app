<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AvanceClient;
use App\Models\Banque;
use App\Models\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AvanceClientController extends Controller
{
    public function indexView(Request $request): InertiaResponse
    {
        $query = AvanceClient::with(['client', 'banqueDepot', 'approvisionnement'])
            ->orderBy('date_cheque', 'desc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }
        if ($request->filled('statut')) {
            $query->where('statut', $request->statut);
        }
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function ($q) use ($s) {
                $q->where('societe_emettrice', 'ILIKE', "%{$s}%")
                  ->orWhere('numero_cheque', 'ILIKE', "%{$s}%")
                  ->orWhere('numero_proforma', 'ILIKE', "%{$s}%")
                  ->orWhereHas('client', fn($cq) => $cq->where('nom', 'ILIKE', "%{$s}%"));
            });
        }

        // Pagination serveur (au lieu d'un ->get() de toute la table).
        $perPage = (int) $request->input('per_page', 20);
        $avancesPaginated = $query->paginate($perPage);
        $avances = $avancesPaginated->getCollection()->map(fn($a) => $a->toApiArray());

        $clients = Client::orderBy('nom')->get()->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
        ]);

        $banques = Banque::with(['comptes.approvisionnements' => function ($q) {
            $q->whereNotNull('reference_bordereau')->orderBy('date_depot', 'desc');
        }])->orderBy('nom')->get()->map(function ($b) {
            $appros = collect();
            foreach ($b->comptes as $c) {
                foreach ($c->approvisionnements as $a) {
                    $appros->push([
                        'id' => $a->id,
                        'reference_bordereau' => $a->reference_bordereau,
                        'date_depot' => $a->date_depot?->format('Y-m-d'),
                    ]);
                }
            }
            return [
                'id' => $b->id,
                'nom' => $b->nom,
                'approvisionnements' => $appros->values(),
            ];
        });

        // total_disponible agrégé en SQL (au lieu de charger toutes les avances).
        $agg = AvanceClient::selectRaw(
            'COALESCE(SUM(montant),0) AS total_avances, '
            .'COALESCE(SUM(montant_utilise),0) AS total_utilise, '
            .'COALESCE(SUM(GREATEST(montant - montant_utilise, 0)),0) AS total_disponible, '
            .'COUNT(*) AS nombre_avances'
        )->first();
        $stats = [
            'total_avances' => (float) $agg->total_avances,
            'total_utilise' => (float) $agg->total_utilise,
            'total_disponible' => (float) $agg->total_disponible,
            'nombre_avances' => (int) $agg->nombre_avances,
        ];

        return Inertia::render('Avances/Index', [
            'avances' => $avances,
            'clients' => $clients,
            'banques' => $banques,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $avancesPaginated->currentPage(),
                'per_page' => $avancesPaginated->perPage(),
                'total' => $avancesPaginated->total(),
                'last_page' => $avancesPaginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'client_id' => $request->input('client_id') ? (int) $request->input('client_id') : null,
                'statut' => $request->input('statut', ''),
            ],
            'user' => [
                'name' => auth()->user()?->name ?? 'Utilisateur',
                'email' => auth()->user()?->email ?? 'user@hospital.bj',
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'societe_emettrice' => ['required', 'string', 'max:255'],
            'numero_cheque' => ['required', 'string', 'max:100'],
            'date_cheque' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'numero_proforma' => ['nullable', 'string', 'max:100'],
            'numero_ligne' => ['nullable', 'string', 'max:50'],
            'institution' => ['nullable', 'string', 'max:255'],
            'banque_depot_id' => ['nullable', 'integer', 'exists:banques,id'],
            'approvisionnement_id' => ['nullable', 'integer', 'exists:approvisionnements_banques,id'],
            'bordereau_depot' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'observations' => ['nullable', 'string'],
        ]);

        $client = Client::findOrFail($request->client_id);

        $bordereauPath = null;
        if ($request->hasFile('bordereau_depot')) {
            $bordereauPath = $request->file('bordereau_depot')->store('bordereaux-avances', 'public');
        }

        try {
            DB::beginTransaction();

            $avance = AvanceClient::create([
                'numero_ligne' => $request->numero_ligne,
                'client_id' => $client->id,
                'client_nom' => $client->nom,
                'societe_emettrice' => $request->societe_emettrice,
                'numero_cheque' => $request->numero_cheque,
                'date_cheque' => $request->date_cheque,
                'montant' => $request->montant,
                'montant_utilise' => 0,
                'numero_proforma' => $request->numero_proforma,
                'statut' => AvanceClient::STATUT_DISPONIBLE,
                'institution' => $request->institution,
                'banque_depot_id' => $request->banque_depot_id,
                'approvisionnement_id' => $request->approvisionnement_id,
                'bordereau_depot_path' => $bordereauPath,
                'observations' => $request->observations,
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name,
            ]);

            DB::commit();

            ActivityLog::log('create', 'avance_client', "Avance de " . number_format($avance->montant, 0, ',', ' ') . " XOF reçue de {$avance->societe_emettrice} pour {$client->nom}", $avance);

            $avance->load(['client', 'banqueDepot', 'approvisionnement']);

            return response()->json([
                'success' => true,
                'message' => 'Avance enregistrée avec succès',
                'data' => $avance->toApiArray(),
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            if ($bordereauPath) {
                Storage::disk('public')->delete($bordereauPath);
            }
            return response()->json([
                'success' => false,
                'message' => "Erreur : " . $e->getMessage(),
            ], 500);
        }
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $avance = AvanceClient::findOrFail($id);

        $request->validate([
            'client_id' => ['required', 'integer', 'exists:clients,id'],
            'societe_emettrice' => ['required', 'string', 'max:255'],
            'numero_cheque' => ['required', 'string', 'max:100'],
            'date_cheque' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'numero_proforma' => ['nullable', 'string', 'max:100'],
            'institution' => ['nullable', 'string', 'max:255'],
            'banque_depot_id' => ['nullable', 'integer', 'exists:banques,id'],
            'approvisionnement_id' => ['nullable', 'integer', 'exists:approvisionnements_banques,id'],
            'observations' => ['nullable', 'string'],
        ]);

        // Empêcher de réduire le montant en dessous de ce qui est déjà utilisé
        if ((float) $request->montant < (float) $avance->montant_utilise) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant ne peut être inférieur à ce qui est déjà utilisé (' . number_format($avance->montant_utilise, 0, ',', ' ') . ' XOF)',
            ], 422);
        }

        $client = Client::findOrFail($request->client_id);

        try {
            DB::beginTransaction();

            $avance->update([
                'client_id' => $client->id,
                'client_nom' => $client->nom,
                'societe_emettrice' => $request->societe_emettrice,
                'numero_cheque' => $request->numero_cheque,
                'date_cheque' => $request->date_cheque,
                'montant' => $request->montant,
                'numero_proforma' => $request->numero_proforma,
                'institution' => $request->institution,
                'banque_depot_id' => $request->banque_depot_id,
                'approvisionnement_id' => $request->approvisionnement_id,
                'observations' => $request->observations,
            ]);

            $avance->recalculerSolde();

            DB::commit();

            ActivityLog::log('update', 'avance_client', "Modification de l'avance #{$avance->id}", $avance);

            $avance->load(['client', 'banqueDepot', 'approvisionnement']);

            return response()->json([
                'success' => true,
                'message' => 'Avance modifiée avec succès',
                'data' => $avance->toApiArray(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => "Erreur : " . $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        $avance = AvanceClient::findOrFail($id);

        if ($avance->reglements()->exists()) {
            return response()->json([
                'success' => false,
                'message' => 'Impossible de supprimer cette avance : des règlements y sont rattachés.',
            ], 422);
        }

        try {
            $avance->delete();
            ActivityLog::log('delete', 'avance_client', "Suppression de l'avance #{$id}");
            return response()->json([
                'success' => true,
                'message' => 'Avance supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => "Erreur : " . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Liste des avances disponibles pour un client (utilisé par le formulaire de règlement).
     */
    public function disponiblesParClient(int $clientId): JsonResponse
    {
        $avances = AvanceClient::where('client_id', $clientId)
            ->whereIn('statut', [AvanceClient::STATUT_DISPONIBLE, AvanceClient::STATUT_PARTIELLEMENT_UTILISEE])
            ->orderBy('date_cheque', 'desc')
            ->get()
            ->map(fn($a) => $a->toApiArray())
            ->filter(fn($a) => $a['montant_restant'] > 0)
            ->values();

        return response()->json(['data' => $avances]);
    }
}
