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
        $perPage = (int) $request->input('per_page', 20);

        // Filtres communs (appliqués au niveau du règlement), partagés par les 3 requêtes
        // ci-dessous : pagination par facture, comptage total et chargement de la page.
        $applyFilters = function ($q) use ($request) {
            if ($request->filled('client_id')) {
                $q->where('client_id', $request->client_id);
            }
            if ($request->filled('type_reglement')) {
                $q->where('type_reglement', $request->type_reglement);
            }
            if ($request->filled('date_debut')) {
                $q->whereDate('date_reglement', '>=', $request->date_debut);
            }
            if ($request->filled('date_fin')) {
                $q->whereDate('date_reglement', '<=', $request->date_fin);
            }
            if ($request->filled('search')) {
                $search = $request->search;
                $q->where(function ($qq) use ($search) {
                    $qq->where('numero_ligne', 'LIKE', "%{$search}%")
                       ->orWhere('reference_cheque', 'LIKE', "%{$search}%")
                       ->orWhere('institution', 'LIKE', "%{$search}%")
                       ->orWhereHas('facture', fn($fq) => $fq->where('reference', 'LIKE', "%{$search}%"))
                       ->orWhereHas('client', fn($cq) => $cq->where('nom', 'LIKE', "%{$search}%"));
                });
            }
        };

        // Pagination côté serveur, au niveau facture (même modèle que Factures Clients) :
        // une page = un ensemble de factures, ordonnées par règlement le plus récent.
        $facturesQuery = ReglementClient::query();
        $applyFilters($facturesQuery);
        $facturesQuery->select('facture_id')
            ->selectRaw('MAX(date_reglement) as last_date')
            ->selectRaw('MAX(id) as last_id')
            ->groupBy('facture_id')
            ->orderByDesc('last_date')
            ->orderByDesc('last_id');
        $facturesPage = $facturesQuery->paginate($perPage);
        $factureIds = collect($facturesPage->items())->pluck('facture_id');

        // Nombre total de règlements (entête), tous filtres appliqués
        $countQuery = ReglementClient::query();
        $applyFilters($countQuery);
        $totalReglements = $countQuery->count();

        // Règlements des factures de la page, regroupés par facture (même structure qu'avant)
        $detailQuery = ReglementClient::with(['facture', 'client', 'banqueDepot', 'approvisionnement']);
        $applyFilters($detailQuery);
        $apiReglements = $detailQuery->whereIn('facture_id', $factureIds)
            ->orderBy('date_reglement', 'desc')
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        $groupesParFacture = [];
        foreach ($apiReglements as $r) {
            $fid = $r['facture']['id'] ?? ('x-' . ($r['facture']['reference'] ?? 'unknown'));
            if (! isset($groupesParFacture[$fid])) {
                $groupesParFacture[$fid] = [
                    'key' => 'f-' . $fid,
                    'facture' => $r['facture'] ?? ['reference' => '-'],
                    'client' => $r['client'] ?? ['nom' => '-'],
                    'reglements' => [],
                    'total_montant_regle' => 0,
                    'count' => 0,
                ];
            }
            $groupesParFacture[$fid]['reglements'][] = $r;
            $groupesParFacture[$fid]['total_montant_regle'] += (float) $r['montant'];
            $groupesParFacture[$fid]['count']++;
        }
        // Conserver l'ordre de pagination des factures
        $reglements = $factureIds
            ->map(fn($fid) => $groupesParFacture[$fid] ?? null)
            ->filter()
            ->values()
            ->all();

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
            'facturesImpayees' => $facturesImpayees,
            'banques' => $banques,
            'stats' => $stats,
            'pagination' => [
                'current_page' => $facturesPage->currentPage(),
                'per_page' => $facturesPage->perPage(),
                'total' => $facturesPage->total(),
                'last_page' => $facturesPage->lastPage(),
            ],
            'totalReglements' => $totalReglements,
            'filters' => [
                'search' => $request->input('search', ''),
                'client_id' => $request->input('client_id'),
                'type_reglement' => $request->input('type_reglement', ''),
                'date_debut' => $request->input('date_debut'),
                'date_fin' => $request->input('date_fin'),
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

            $facture->enregistrerPaiement($request->montant);

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
            'observations' => ['nullable', 'string'],
            'bordereau_depot' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'montant_rejet' => ['nullable', 'numeric', 'min:0'],
        ]);

        $bordereauPath = $reglement->bordereau_depot_path;
        if ($request->hasFile('bordereau_depot')) {
            if ($bordereauPath) {
                \Illuminate\Support\Facades\Storage::disk('public')->delete($bordereauPath);
            }
            $bordereauPath = $request->file('bordereau_depot')->store('bordereaux-depot', 'public');
        }

        $ancienMontant = (float) $reglement->montant;
        $nouveauMontant = (float) $request->montant;
        $difference = $nouveauMontant - $ancienMontant;

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
                'observations' => $request->observations,
                'bordereau_depot_path' => $bordereauPath,
                'montant_rejet' => (float) ($request->montant_rejet ?? $reglement->montant_rejet ?? 0),
            ]);

            // Mettre à jour la facture
            $facture->montant_paye = (float) $facture->montant_paye + $difference;
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
