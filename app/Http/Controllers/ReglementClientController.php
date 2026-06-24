<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AvanceClient;
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
        $query = ReglementClient::with(['facture', 'client', 'banqueDepot', 'approvisionnement', 'avance'])
            ->orderBy('date_reglement', 'desc');

        if ($request->filled('client_id')) {
            $query->where('client_id', $request->client_id);
        }

        if ($request->filled('type_reglement')) {
            $query->where('type_reglement', $request->type_reglement);
        }

        if ($request->filled('date_debut')) {
            $query->whereDate('date_reglement', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_reglement', '<=', $request->date_fin);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('numero_ligne', 'ILIKE', "%{$search}%")
                  ->orWhere('reference_cheque', 'ILIKE', "%{$search}%")
                  ->orWhere('institution', 'ILIKE', "%{$search}%")
                  ->orWhereHas('facture', fn($fq) => $fq->where('reference', 'ILIKE', "%{$search}%"))
                  ->orWhereHas('client', fn($cq) => $cq->where('nom', 'ILIKE', "%{$search}%"));
            });
        }

        // Pagination serveur (au lieu d'un ->get() de toute la table).
        $perPage = (int) $request->input('per_page', 20);
        $reglementsPaginated = $query->paginate($perPage);
        $reglements = $reglementsPaginated->getCollection()->map(fn($r) => $r->toApiArray());

        $clients = Client::orderBy('nom')->get()->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
        ]);

        // NB : la liste des factures impayées n'est PLUS injectée dans les props
        // (elle saturait l'historique Inertia → erreur Firefox sur gros volume).
        // Le sélecteur du formulaire « Nouveau règlement » la cherche via l'API
        // /api/factures-clients/impayees (recherche serveur).

        $stats = [
            'total_reglements' => (float) ReglementClient::sum('montant'),
            'reglements_mois' => (float) ReglementClient::whereMonth('date_reglement', now()->month)
                ->whereYear('date_reglement', now()->year)
                ->sum('montant'),
            'nombre_reglements' => ReglementClient::count(),
            'montant_moyen' => (float) (ReglementClient::avg('montant') ?? 0),
        ];

        // Banques + bordereaux (pour la modification de la banque de dépôt d'un règlement)
        $banques = Banque::with(['comptes.approvisionnements' => function ($q) {
            $q->whereNotNull('reference_bordereau')->orderBy('date_depot', 'desc');
        }])
            ->orderBy('nom')
            ->get()
            ->map(function ($b) {
                $approvisionnements = collect();
                foreach ($b->comptes as $compte) {
                    foreach ($compte->approvisionnements as $appro) {
                        $approvisionnements->push([
                            'id' => $appro->id,
                            'reference_bordereau' => $appro->reference_bordereau,
                            'date_depot' => $appro->date_depot?->format('Y-m-d'),
                        ]);
                    }
                }
                return [
                    'id' => $b->id,
                    'nom' => $b->nom,
                    'approvisionnements' => $approvisionnements->values(),
                ];
            });

        return Inertia::render('ReglementClients/Index', [
            'reglements' => $reglements,
            'clients' => $clients,
            'banques' => $banques,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $reglementsPaginated->currentPage(),
                'per_page' => $reglementsPaginated->perPage(),
                'total' => $reglementsPaginated->total(),
                'last_page' => $reglementsPaginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'client_id' => $request->input('client_id') ? (int) $request->input('client_id') : null,
                'type_reglement' => $request->input('type_reglement', ''),
            ],
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
            'type_reglement' => ['nullable', 'string', 'in:reglement,perte'],
            'institution' => ['nullable', 'string', 'max:255'],
            'reference_cheque' => ['nullable', 'string', 'max:100'],
            'banque_depot_id' => ['nullable', 'integer', 'exists:banques,id'],
            'approvisionnement_id' => ['nullable', 'integer', 'exists:approvisionnements_banques,id'],
            'avance_id' => ['nullable', 'integer', 'exists:avances_clients,id'],
            'observations' => ['nullable', 'string'],
            'bordereau_depot' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'montant_rejet' => ['nullable', 'numeric', 'min:0'],
        ]);

        $facture = FactureClient::findOrFail($request->facture_id);

        // Si imputation sur avance : vérifier appartenance au client et solde disponible
        $avance = null;
        if ($request->filled('avance_id')) {
            $avance = AvanceClient::findOrFail($request->avance_id);
            if ($avance->client_id !== $facture->client_id) {
                return response()->json([
                    'success' => false,
                    'message' => "L'avance sélectionnée n'appartient pas à ce client.",
                ], 422);
            }
            if ((float) $request->montant > $avance->montant_restant) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant dépasse le solde restant de cette avance (' . number_format($avance->montant_restant, 0, ',', ' ') . ' XOF)',
                ], 422);
            }
        }

        // Vérifier le solde du bordereau (approvisionnement) si imputation dessus
        if ($request->filled('approvisionnement_id')) {
            $appro = \App\Models\ApprovisionnementBanque::withSum('reglementsClients', 'montant')
                ->find($request->approvisionnement_id);
            if ($appro) {
                $soldeBordereau = (float) $appro->montant - (float) ($appro->reglements_clients_sum_montant ?? 0);
                if ((float) $request->montant > $soldeBordereau) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le montant dépasse le solde du bordereau (' . number_format($soldeBordereau, 0, ',', ' ') . ' XOF)',
                    ], 422);
                }
            }
        }

        $bordereauPath = null;
        if ($request->hasFile('bordereau_depot')) {
            $bordereauPath = $request->file('bordereau_depot')->store('bordereaux-depot', 'public');
        }

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
                'type_reglement' => $request->type_reglement ?? 'reglement',
                'date_reglement' => $request->date_reglement,
                'facture_id' => $facture->id,
                'client_id' => $facture->client_id,
                'client_nom' => $facture->client_nom ?: $facture->client?->nom,
                'facture_reference' => $facture->reference,
                'montant' => $request->montant,
                'institution' => $request->institution,
                'reference_cheque' => $request->reference_cheque,
                'banque_depot_id' => $request->banque_depot_id,
                'approvisionnement_id' => $request->approvisionnement_id,
                'avance_id' => $request->avance_id,
                'observations' => $request->observations,
                'bordereau_depot_path' => $bordereauPath,
                'montant_rejet' => (float) ($request->montant_rejet ?? 0),
                'created_by' => auth()->id(),
                'created_by_name' => auth()->user()->name,
            ]);

            // Recalcul homogène : payé + rejet + perte (le rejet fait partie du règlement).
            $facture->load('reglements');
            $facture->recalculerSoldes();

            if ($avance) {
                $avance->recalculerSolde();
            }

            DB::commit();

            $montantReglement = (float) $request->montant;
            ActivityLog::log('create', 'reglement_client', "Règlement de " . number_format($montantReglement, 0, ',', ' ') . " XOF sur facture {$facture->reference}", $reglement, ['montant' => $montantReglement]);

            $reglement->load(['facture', 'client', 'banqueDepot', 'approvisionnement']);

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
     * Modifier un règlement client
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $reglement = ReglementClient::findOrFail($id);
        $facture = $reglement->facture;

        $request->validate([
            'date_reglement' => ['required', 'date'],
            'montant' => ['required', 'numeric', 'min:1'],
            'numero_ligne' => ['nullable', 'string', 'max:50'],
            'type_reglement' => ['nullable', 'string', 'in:reglement,perte'],
            'institution' => ['nullable', 'string', 'max:255'],
            'reference_cheque' => ['nullable', 'string', 'max:100'],
            'banque_depot_id' => ['nullable', 'integer', 'exists:banques,id'],
            'approvisionnement_id' => ['nullable', 'integer', 'exists:approvisionnements_banques,id'],
            'avance_id' => ['nullable', 'integer', 'exists:avances_clients,id'],
            'observations' => ['nullable', 'string'],
            'bordereau_depot' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'montant_rejet' => ['nullable', 'numeric', 'min:0'],
        ]);

        $ancienMontant = (float) $reglement->montant;
        $nouveauMontant = (float) $request->montant;
        $ancienneAvanceId = $reglement->avance_id;

        // Imputation sur avance : appartenance au client + solde disponible.
        // On réintègre l'ancien montant si on reste sur la même avance (il y est déjà compté).
        $avance = null;
        if ($request->filled('avance_id')) {
            $avance = AvanceClient::findOrFail($request->avance_id);
            if ($avance->client_id !== $facture->client_id) {
                return response()->json([
                    'success' => false,
                    'message' => "L'avance sélectionnée n'appartient pas à ce client.",
                ], 422);
            }
            $soldeDispo = (float) $avance->montant_restant
                + ($avance->id === $ancienneAvanceId ? $ancienMontant : 0);
            if ($nouveauMontant > $soldeDispo) {
                return response()->json([
                    'success' => false,
                    'message' => 'Le montant dépasse le solde restant de cette avance (' . number_format($soldeDispo, 0, ',', ' ') . ' XOF)',
                ], 422);
            }
        }

        // Solde du bordereau (en réintégrant l'ancien montant si on reste sur le même bordereau).
        if ($request->filled('approvisionnement_id')) {
            $appro = \App\Models\ApprovisionnementBanque::withSum('reglementsClients', 'montant')
                ->find($request->approvisionnement_id);
            if ($appro) {
                $dejaUtilise = (float) ($appro->reglements_clients_sum_montant ?? 0);
                $offset = ($reglement->approvisionnement_id === (int) $request->approvisionnement_id) ? $ancienMontant : 0;
                $soldeBordereau = (float) $appro->montant - $dejaUtilise + $offset;
                if ($nouveauMontant > $soldeBordereau) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Le montant dépasse le solde du bordereau (' . number_format($soldeBordereau, 0, ',', ' ') . ' XOF)',
                    ], 422);
                }
            }
        }

        $bordereauPath = $reglement->bordereau_depot_path;
        if ($request->hasFile('bordereau_depot')) {
            if ($bordereauPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($bordereauPath);
            }
            $bordereauPath = $request->file('bordereau_depot')->store('bordereaux-depot', 'public');
        }

        // Vérifier que le nouveau montant ne dépasse pas le reste à payer + ancien montant
        $resteDisponible = (float) $facture->reste_a_payer + $ancienMontant;
        if ($nouveauMontant > $resteDisponible) {
            return response()->json([
                'success' => false,
                'message' => 'Le montant dépasse le reste à payer (' . number_format($resteDisponible, 0, ',', ' ') . ' XOF)',
            ], 422);
        }

        try {
            DB::beginTransaction();

            $reglement->update([
                'date_reglement' => $request->date_reglement,
                'montant' => $nouveauMontant,
                'type_reglement' => $request->type_reglement ?? $reglement->type_reglement,
                'numero_ligne' => $request->numero_ligne,
                'institution' => $request->institution,
                'reference_cheque' => $request->reference_cheque,
                'banque_depot_id' => $request->banque_depot_id,
                'approvisionnement_id' => $request->approvisionnement_id,
                'avance_id' => $request->avance_id,
                'observations' => $request->observations,
                'bordereau_depot_path' => $bordereauPath,
                'montant_rejet' => (float) ($request->montant_rejet ?? $reglement->montant_rejet ?? 0),
            ]);

            // Recalcul des soldes d'avance : l'ancienne (si elle change/est retirée) et la nouvelle.
            if ($ancienneAvanceId && $ancienneAvanceId !== ($avance?->id)) {
                AvanceClient::find($ancienneAvanceId)?->recalculerSolde();
            }
            if ($avance) {
                $avance->recalculerSolde();
            }

            // Recalcul homogène à partir de l'ensemble des règlements (payé + rejet + perte).
            $facture->load('reglements');
            $facture->recalculerSoldes();

            DB::commit();

            ActivityLog::log('update', 'reglement_client', "Modification du règlement #{$reglement->id}", $reglement);

            $reglement->load(['facture', 'client', 'banqueDepot', 'approvisionnement']);

            return response()->json([
                'success' => true,
                'message' => 'Règlement modifié avec succès',
                'data' => $reglement->toApiArray(),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la modification : ' . $e->getMessage(),
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
        $avance = $reglement->avance;

        try {
            DB::beginTransaction();

            $montant = (float) $reglement->montant;
            $reglement->delete();

            if ($avance) {
                $avance->recalculerSolde();
            }

            // Recalcul homogène après suppression du règlement (payé + rejet + perte).
            $facture->load('reglements');
            $facture->recalculerSoldes();

            DB::commit();

            ActivityLog::log('delete', 'reglement_client', "Suppression du règlement #{$id}", null, ['montant' => $montant]);

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
