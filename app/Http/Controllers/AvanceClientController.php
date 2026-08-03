<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\AvanceClient;
use App\Models\Banque;
use App\Models\Client;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class AvanceClientController extends Controller
{
    public function indexView(Request $request): InertiaResponse
    {
        $query = AvanceClient::with([
                'client',
                'societeEmettrice.compteComptable',
                'beneficiaires.compteComptable',
                'banqueDepot',
                'approvisionnement',
            ])
            // Société émettrice en premier dans l'affichage, puis par date de chèque.
            ->orderBy('societe_emettrice_client_id')
            ->orderBy('date_cheque', 'desc');

        if ($request->filled('client_id')) {
            $cid = (int) $request->client_id;
            $query->where(function ($q) use ($cid) {
                $q->where('client_id', $cid)
                  ->orWhere('societe_emettrice_client_id', $cid);
            });
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
                  ->orWhereHas('societeEmettrice', fn($cq) => $cq->where('nom', 'ILIKE', "%{$s}%"))
                  ->orWhereRaw('CAST(beneficiaires_noms AS TEXT) ILIKE ?', ["%{$s}%"])
                  ->orWhereHas('beneficiaires', fn($cq) => $cq->where('clients.nom', 'ILIKE', "%{$s}%"))
                  ->orWhereHas('client', fn($cq) => $cq->where('nom', 'ILIKE', "%{$s}%"));
            });
        }

        // Pagination serveur (au lieu d'un ->get() de toute la table).
        $perPage = (int) $request->input('per_page', 20);
        $avancesPaginated = $query->paginate($perPage);
        $avances = $avancesPaginated->getCollection()->map(fn($a) => $a->toApiArray());

        // Liste de tous les clients (pour le filtre + le multi-select bénéficiaires).
        $clients = Client::with('compteComptable')->orderBy('nom')->get()->map(fn($c) => [
            'id' => $c->id,
            'nom' => $c->nom,
            'type_client' => $c->type_client,
            'numero_compte' => $c->compteComptable?->numero_compte,
        ]);

        // Clients type "société" uniquement (pour le select société émettrice).
        $societes = Client::with('compteComptable')
            ->where('type_client', 'societe')
            ->orderBy('nom')
            ->get()
            ->map(fn($c) => [
                'id' => $c->id,
                'nom' => $c->nom,
                'numero_compte' => $c->compteComptable?->numero_compte,
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
            'societes' => $societes,
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
            'societe_emettrice_client_id' => ['required', 'integer', 'exists:clients,id'],
            'beneficiaires' => ['required', 'array', 'min:1'],
            'beneficiaires.*' => ['string', 'distinct:ignore_case', 'max:255'],
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

        $emetteur = $this->resolveSocieteEmettrice($request->societe_emettrice_client_id);
        if (! $emetteur) {
            return response()->json([
                'success' => false,
                'message' => 'La société émettrice doit être un client de type « Société ».',
                'errors' => ['societe_emettrice_client_id' => ['Le client sélectionné n\'est pas une société.']],
            ], 422);
        }

        $beneficiaires = $this->normaliserBeneficiaires($request->beneficiaires);

        $bordereauPath = null;
        if ($request->hasFile('bordereau_depot')) {
            $bordereauPath = $request->file('bordereau_depot')->store('bordereaux-avances', 'public');
        }

        try {
            DB::beginTransaction();

            $avance = AvanceClient::create([
                'numero_ligne' => $request->numero_ligne,
                'client_id' => $emetteur->id,
                'client_nom' => $emetteur->nom,
                'societe_emettrice_client_id' => $emetteur->id,
                'societe_emettrice' => $emetteur->nom,
                'beneficiaires_noms' => $beneficiaires->all(),
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

            ActivityLog::log('create', 'avance_client', "Avance de " . number_format($avance->montant, 0, ',', ' ') . " XOF reçue de {$avance->societe_emettrice} pour " . $beneficiaires->join(', '), $avance);

            $avance->load(['client', 'societeEmettrice.compteComptable', 'beneficiaires.compteComptable', 'banqueDepot', 'approvisionnement']);

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

    /**
     * Renvoie le client émetteur s'il existe ET est de type société, sinon null.
     */
    private function resolveSocieteEmettrice(int $clientId): ?Client
    {
        $client = Client::find($clientId);
        if (! $client || $client->type_client !== 'societe') {
            return null;
        }
        return $client;
    }

    private function normaliserBeneficiaires(array $beneficiaires)
    {
        $noms = collect($beneficiaires)
            ->map(fn ($nom) => trim($nom))
            ->filter()
            ->unique(fn ($nom) => mb_strtolower($nom))
            ->values();

        if ($noms->isEmpty()) {
            throw ValidationException::withMessages([
                'beneficiaires' => ['Saisissez au moins un nom de bénéficiaire.'],
            ]);
        }

        return $noms;
    }

    public function update(Request $request, int $id): JsonResponse
    {
        $avance = AvanceClient::findOrFail($id);

        $request->validate([
            'societe_emettrice_client_id' => ['required', 'integer', 'exists:clients,id'],
            'beneficiaires' => ['required', 'array', 'min:1'],
            'beneficiaires.*' => ['string', 'distinct:ignore_case', 'max:255'],
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

        $emetteur = $this->resolveSocieteEmettrice($request->societe_emettrice_client_id);
        if (! $emetteur) {
            return response()->json([
                'success' => false,
                'message' => 'La société émettrice doit être un client de type « Société ».',
                'errors' => ['societe_emettrice_client_id' => ['Le client sélectionné n\'est pas une société.']],
            ], 422);
        }

        $beneficiaires = $this->normaliserBeneficiaires($request->beneficiaires);

        try {
            DB::beginTransaction();

            $avance->update([
                'client_id' => $emetteur->id,
                'client_nom' => $emetteur->nom,
                'societe_emettrice_client_id' => $emetteur->id,
                'societe_emettrice' => $emetteur->nom,
                'beneficiaires_noms' => $beneficiaires->all(),
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

            $avance->load(['client', 'societeEmettrice.compteComptable', 'beneficiaires.compteComptable', 'banqueDepot', 'approvisionnement']);

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
        $avances = AvanceClient::with(['societeEmettrice.compteComptable', 'beneficiaires.compteComptable'])
            ->where(function ($q) use ($clientId) {
                $q->where('societe_emettrice_client_id', $clientId)
                  ->orWhere(function ($legacy) use ($clientId) {
                      $legacy->whereNull('societe_emettrice_client_id')->where('client_id', $clientId);
                  });
            })
            ->whereIn('statut', [AvanceClient::STATUT_DISPONIBLE, AvanceClient::STATUT_PARTIELLEMENT_UTILISEE])
            ->orderBy('date_cheque', 'desc')
            ->get()
            ->map(fn($a) => $a->toApiArray())
            ->filter(fn($a) => $a['montant_restant'] > 0)
            ->values();

        return response()->json(['data' => $avances]);
    }

    /**
     * Liste des avances d'une période, pour alimenter le sélecteur « société émettrice »
     * de l'état des avances. Une entrée = un chèque/avance, labellisé
     * « NOM SOCIÉTÉ – date chèque – réf chèque – montant ».
     */
    public function listePeriode(Request $request): JsonResponse
    {
        $query = AvanceClient::with('societeEmettrice')
            ->orderBy('societe_emettrice_client_id')
            ->orderBy('date_cheque', 'desc');

        if ($request->filled('date_debut')) {
            $query->whereDate('date_cheque', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_cheque', '<=', $request->date_fin);
        }

        $data = $query->get()->map(function ($a) {
            $nom = $a->societeEmettrice?->nom ?: $a->societe_emettrice ?: 'Société ?';
            $date = $a->date_cheque?->format('d/m/Y') ?? '—';
            $cheque = $a->numero_cheque ?: '—';
            $montant = number_format((float) $a->montant, 0, ',', ' ');
            return [
                'id' => $a->id,
                'label' => "{$nom} – {$date} – {$cheque} – {$montant}",
            ];
        })->values();

        return response()->json(['data' => $data]);
    }

    /**
     * État PDF des avances clients (#11) — version détaillée, une page par avance.
     * Toujours périodique (date_debut / date_fin sur date_cheque).
     * Filtre optionnel `avance_id` : limite l'état à un seul chèque/avance.
     *
     * Pour chaque avance : en-tête (société émettrice, date chèque, réf chèque, montant),
     * puis le détail de TOUS les bénéficiaires déclarés avec, pour chaque facture réglée
     * depuis l'avance : réf facture, date facture, montant facture, montant réglé (utilisé).
     * En pied : total utilisé et restant de l'avance.
     */
    public function etatAvancesPdf(Request $request)
    {
        @ini_set('memory_limit', '1024M');
        @set_time_limit(300);

        $query = AvanceClient::with([
                'societeEmettrice.compteComptable',
                'beneficiaires',
                'reglements.facture',
                'reglements.client',
            ])
            ->orderBy('societe_emettrice_client_id')
            ->orderBy('date_cheque', 'desc');

        if ($request->filled('avance_id')) {
            $query->where('id', (int) $request->avance_id);
        }
        if ($request->filled('date_debut')) {
            $query->whereDate('date_cheque', '>=', $request->date_debut);
        }
        if ($request->filled('date_fin')) {
            $query->whereDate('date_cheque', '<=', $request->date_fin);
        }

        $avances = $query->get();

        $lignes = $avances->map(function ($a) {
            $emetteur = $a->societeEmettrice;
            $mapReglement = fn ($r, $nom) => [
                'beneficiaire' => $nom,
                'facture_ref' => $r->facture_reference ?: $r->facture?->reference,
                'date_facture' => $r->facture?->date_facture?->format('d/m/Y'),
                'montant_facture' => (float) ($r->facture?->montant ?? 0),
                'montant_regle' => (float) $r->montant,
            ];

            $rows = [];

            $noms = collect($a->beneficiaires_noms);
            if ($noms->isEmpty()) {
                $noms = $a->beneficiaires->map(fn ($b) => $b->pivot->client_nom ?: $b->nom);
            }

            // Les noms de patients sont descriptifs : il n'existe volontairement aucun lien
            // technique avec les clients/factures. On les restitue donc sans inventer une
            // ventilation, puis on affiche séparément tous les règlements réellement liés.
            foreach ($noms->filter()->unique() as $nom) {
                $rows[] = [
                    'beneficiaire' => $nom,
                    'facture_ref' => null,
                    'date_facture' => null,
                    'montant_facture' => null,
                    'montant_regle' => null,
                ];
            }

            foreach ($a->reglements as $r) {
                $rows[] = $mapReglement($r, $r->client?->nom ?? '-');
            }

            $utilise = (float) $a->reglements->sum('montant');

            return [
                'emetteur_compte' => $emetteur?->compteComptable?->numero_compte,
                'emetteur_nom' => $emetteur?->nom ?: $a->societe_emettrice,
                'numero_cheque' => $a->numero_cheque,
                'date_cheque' => $a->date_cheque?->format('d/m/Y'),
                'numero_proforma' => $a->numero_proforma,
                'montant' => (float) $a->montant,
                'montant_utilise' => $utilise,
                'montant_restant' => max(0, (float) $a->montant - $utilise),
                'statut_libelle' => AvanceClient::getStatutsLabels()[$a->statut] ?? $a->statut,
                'rows' => $rows,
            ];
        })->values()->all();

        $data = [
            'titre' => 'ÉTAT DES AVANCES CLIENTS',
            'avances' => $lignes,
            'periode' => [
                'debut' => $request->input('date_debut'),
                'fin' => $request->input('date_fin'),
            ],
            'generatedBy' => auth()->user()?->name ?? 'Utilisateur',
            'generatedAt' => now()->format('d/m/Y à H:i'),
        ];

        $pdf = Pdf::loadView('pdf.rapports-clients.etat-avances', $data);
        $pdf->setPaper('a4', 'portrait');

        return $request->query('action') === 'stream'
            ? $pdf->stream('etat-avances.pdf')
            : $pdf->download('etat-avances.pdf');
    }
}
