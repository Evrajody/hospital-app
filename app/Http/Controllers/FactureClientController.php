<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
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
    public function indexView(Request $request): InertiaResponse
    {
        $query = FactureClient::with('client');

        // Recherche
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('reference', 'ILIKE', "%{$search}%")
                  ->orWhereHas('client', function ($cq) use ($search) {
                      $cq->where('nom', 'ILIKE', "%{$search}%");
                  });
            });
        }

        // Filtre par client
        if ($clientId = $request->input('client_id')) {
            $query->where('client_id', $clientId);
        }

        // Filtre par statut
        if ($statut = $request->input('statut')) {
            $query->where('statut', $statut);
        }

        // Tri
        $sort = $request->input('sort', 'date_facture');
        $order = $request->input('order', 'desc');
        $query->orderBy($sort, $order);

        // Pagination
        $perPage = $request->input('per_page', 20);
        $facturesPaginated = $query->paginate($perPage);

        $factures = $facturesPaginated->getCollection()->map(fn($f) => $f->toApiArray());

        $clients = Client::orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'code' => $c->compteComptable?->numero_compte ?? '-',
            ]);

        $prochaineReference = FactureClient::genererReference();

        // Stats globales (non filtrées)
        $allFactures = FactureClient::all();
        $stats = [
            'total_facture' => $allFactures->sum('montant'),
            'total_paye' => $allFactures->sum('montant_paye'),
            'total_reste' => $allFactures->sum('reste_a_payer'),
        ];

        return Inertia::render('Clients/Factures/Index', [
            'factures' => $factures,
            'clients' => $clients,
            'prochaineReference' => $prochaineReference,
            'pagination' => [
                'current_page' => $facturesPaginated->currentPage(),
                'per_page' => $facturesPaginated->perPage(),
                'total' => $facturesPaginated->total(),
                'last_page' => $facturesPaginated->lastPage(),
            ],
            'filters' => [
                'search' => $request->input('search', ''),
                'statut' => $request->input('statut', ''),
            ],
            'stats' => $stats,
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

        $reglements = ReglementClient::with(['banqueDepot', 'approvisionnement'])
            ->where('facture_id', $id)
            ->orderBy('date_reglement', 'desc')
            ->get()
            ->map(fn($r) => $r->toApiArray());

        // Pour le dropdown de la page règlement on liste les approvisionnements (référence bordereau)
        // regroupés par banque. Le compte bancaire est résolu via l'approvisionnement.
        $banques = Banque::with(['comptes.approvisionnements' => function ($q) {
            $q->whereNotNull('reference_bordereau')
              ->orderBy('date_depot', 'desc')
              ->with('reglementsClients');
        }])
            ->orderBy('nom')
            ->get()
            ->map(function ($b) {
                $approvisionnements = collect();
                foreach ($b->comptes as $compte) {
                    foreach ($compte->approvisionnements as $appro) {
                        $bordereauMontant = (float) $appro->montant;
                        $bordereauUtilise = (float) $appro->reglementsClients->sum('montant');
                        $approvisionnements->push([
                            'id' => $appro->id,
                            'reference_bordereau' => $appro->reference_bordereau,
                            'date_depot' => $appro->date_depot?->format('Y-m-d'),
                            'compte_bancaire_id' => $compte->id,
                            'compte_numero' => $compte->numero_compte,
                            'montant' => $bordereauMontant,
                            'montant_utilise' => $bordereauUtilise,
                            'montant_restant' => $bordereauMontant - $bordereauUtilise,
                        ]);
                    }
                }
                return [
                    'id' => $b->id,
                    'nom' => $b->nom,
                    // On masque les bordereaux épuisés (solde restant ≤ 0)
                    'approvisionnements' => $approvisionnements
                        ->filter(fn($a) => $a['montant_restant'] > 0)
                        ->values(),
                ];
            });

        $institutions = ReglementClient::getInstitutions();

        // Avances disponibles pour ce client
        $avancesDisponibles = \App\Models\AvanceClient::where('client_id', $facture->client_id)
            ->whereIn('statut', [
                \App\Models\AvanceClient::STATUT_DISPONIBLE,
                \App\Models\AvanceClient::STATUT_PARTIELLEMENT_UTILISEE,
            ])
            ->orderBy('date_cheque', 'desc')
            ->get()
            ->map(fn($a) => $a->toApiArray())
            ->filter(fn($a) => $a['montant_restant'] > 0)
            ->values();

        return Inertia::render('Clients/Factures/Reglement', [
            'facture' => $facture->toApiArray(),
            'reglements' => $reglements,
            'banques' => $banques,
            'institutions' => $institutions,
            'avancesDisponibles' => $avancesDisponibles,
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

        $ristourne = $request->ristourne ?? 0;

        $client = \App\Models\Client::find($request->client_id);

        $facture = FactureClient::create([
            'reference' => $request->reference,
            'date_facture' => $request->date_facture,
            'montant' => $request->montant,
            'ristourne' => $ristourne,
            'client_id' => $request->client_id,
            'client_nom' => $client?->nom,
            'reste_a_payer' => $request->montant - $ristourne,
            'statut' => FactureClient::STATUT_NON_PAYEE,
            'created_by' => auth()->id(),
            'created_by_name' => auth()->user()->name,
        ]);

        $facture->load('client');

        ActivityLog::log('create', 'facture_client', "Création de la facture {$facture->reference}", $facture);

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

        // Re-snapshot si le client change
        $clientNom = $facture->client_nom;
        if ($request->client_id != $facture->client_id) {
            $clientNom = \App\Models\Client::find($request->client_id)?->nom;
        }

        $facture->update([
            'reference' => $request->reference,
            'date_facture' => $request->date_facture,
            'montant' => $request->montant,
            'ristourne' => $request->ristourne ?? 0,
            'client_id' => $request->client_id,
            'client_nom' => $clientNom,
        ]);

        $facture->load('client');

        ActivityLog::log('update', 'facture_client', "Modification de la facture {$facture->reference}", $facture);

        return response()->json([
            'success' => true,
            'message' => 'Facture modifiée avec succès',
            'data' => $facture->toApiArray(),
        ]);
    }

    /**
     * Données JSON "État de Règlement" d'une facture client (drawer)
     */
    public function etatReglementData(int $id): JsonResponse
    {
        $facture = FactureClient::with('client.compteComptable')->findOrFail($id);

        $reglements = ReglementClient::where('facture_id', $id)
            ->orderBy('date_reglement')
            ->orderBy('id')
            ->get();

        $montant = (float) $facture->montant;
        $ristourne = (float) ($facture->ristourne ?? 0);
        $montantDu = $montant - $ristourne;
        $totalReglements = (float) $reglements->sum('montant');
        $solde = $montantDu - $totalReglements;

        return response()->json([
            'success' => true,
            'etablissement' => \App\Models\Setting::getEtablissement(),
            'client' => [
                'nom' => $facture->client_nom ?: $facture->client?->nom,
                'code' => $facture->client?->compteComptable?->numero_compte,
            ],
            'facture' => [
                'id' => $facture->id,
                'reference' => $facture->reference,
                'date' => $facture->date_facture?->format('d/m/Y'),
                'montant' => number_format($montant, 0, ',', ' '),
                'ristourne' => number_format($ristourne, 0, ',', ' '),
                'net_a_payer' => number_format($montantDu, 0, ',', ' '),
            ],
            'reglements' => $reglements->values()->map(fn($r) => [
                'date_reglement' => $r->date_reglement?->format('d/m/Y'),
                'type' => $r->type_reglement_libelle ?: 'Règlement',
                'institution' => $r->institution ?: '-',
                'reference' => $r->reference_cheque ?: '-',
                'montant' => number_format((float) $r->montant, 0, ',', ' '),
            ])->toArray(),
            'total_reglements' => number_format($totalReglements, 0, ',', ' '),
            'montant_du' => number_format($montantDu, 0, ',', ' '),
            'solde' => number_format($solde, 0, ',', ' '),
        ]);
    }

    /**
     * PDF "État de Règlement" d'une facture client
     */
    public function etatReglementPdf(int $id)
    {
        $facture = FactureClient::with('client.compteComptable')->findOrFail($id);

        $reglements = ReglementClient::where('facture_id', $id)
            ->orderBy('date_reglement')
            ->orderBy('id')
            ->get();

        $montant = (float) $facture->montant;
        $ristourne = (float) ($facture->ristourne ?? 0);
        $montantDu = $montant - $ristourne;
        $totalReglements = (float) $reglements->sum('montant');
        $solde = $montantDu - $totalReglements;

        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('pdf.etat-reglement-facture-client', [
            'facture' => $facture,
            'client' => $facture->client,
            'clientNom' => $facture->client_nom ?: $facture->client?->nom,
            'reglements' => $reglements,
            'montant' => $montant,
            'ristourne' => $ristourne,
            'montantDu' => $montantDu,
            'totalReglements' => $totalReglements,
            'solde' => $solde,
            'user' => auth()->user(),
            'etablissement' => \App\Models\Setting::getEtablissement(),
        ]);

        $filename = "etat-reglement-client-{$facture->id}.pdf";
        return request()->query('action') === 'stream'
            ? $pdf->stream($filename)
            : $pdf->download($filename);
    }

    /**
     * Marquer une facture client comme soldée
     */
    public function solder(Request $request, int $id): JsonResponse
    {
        $facture = FactureClient::findOrFail($id);

        if ($facture->statut === FactureClient::STATUT_PAYEE) {
            return response()->json([
                'success' => false,
                'message' => 'Cette facture est déjà soldée',
            ], 422);
        }

        // Date à laquelle la facture est marquée comme soldée (par défaut aujourd'hui,
        // modifiable). Le statut reste 'payee' (comportement inchangé) : c'est la présence
        // d'une date_solde qui distingue une facture marquée soldée manuellement.
        // Aucun règlement n'est créé.
        $dateSolde = $request->input('date_solde', now()->toDateString());

        $facture->update([
            'statut' => FactureClient::STATUT_PAYEE,
            'reste_a_payer' => 0,
            'date_solde' => $dateSolde,
        ]);

        $facture->load('client');

        ActivityLog::log('settle', 'facture_client', "Facture {$facture->reference} marquée comme soldée au " . \Carbon\Carbon::parse($dateSolde)->format('d/m/Y'), $facture);

        return response()->json([
            'success' => true,
            'message' => 'Facture marquée comme soldée',
            'data' => $facture->toApiArray(),
        ]);
    }

    /**
     * Supprimer une facture client (API)
     */
    public function destroy(int $id): JsonResponse
    {
        $facture = FactureClient::findOrFail($id);

        // Pas de suppression en cascade : on bloque si la facture fait l'objet de règlements.
        $nbReglements = $facture->reglements()->count();
        if ($nbReglements > 0) {
            return response()->json([
                'success' => false,
                'message' => "Impossible de supprimer cette facture car elle fait l'objet de {$nbReglements} règlement(s). Veuillez d'abord supprimer les règlements associés.",
            ], 422);
        }

        $reference = $facture->reference;
        $facture->delete();

        ActivityLog::log('delete', 'facture_client', "Suppression de la facture {$reference}", null, ['reference' => $reference]);

        return response()->json([
            'success' => true,
            'message' => 'Facture supprimée avec succès',
        ]);
    }
}
